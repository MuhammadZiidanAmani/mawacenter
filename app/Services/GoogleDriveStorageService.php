<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class GoogleDriveStorageService
{
    private const DRIVE_SCOPE = 'https://www.googleapis.com/auth/drive.file';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const FILES_URL = 'https://www.googleapis.com/drive/v3/files';

    private const UPLOAD_URL = 'https://www.googleapis.com/upload/drive/v3/files';

    /**
     * @return array{file_id: string, metadata: array{name: string, mime_type: string, size: int, uploaded_at: string}}
     */
    public function upload(UploadedFile $file): array
    {
        $credentials = $this->credentials();
        $token = $this->accessToken($credentials);
        $folderId = (string) config('services.google_drive.folder_id');
        if ($folderId === '') {
            throw new GoogleDriveStorageException('Penyimpanan bukti transfer Google Drive belum dikonfigurasi.');
        }

        $mimeType = $file->getMimeType() ?: 'application/octet-stream';
        $extension = Str::lower($file->getClientOriginalExtension());
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'bin';
        $name = 'payment-proof-'.now()->format('YmdHis').'-'.Str::uuid().'.'.$extension;
        $fileId = null;

        try {
            $created = Http::timeout(30)
                ->acceptJson()
                ->withToken($token)
                ->post(self::FILES_URL.'?fields=id,name,mimeType,size,createdTime', [
                    'name' => $name,
                    'mimeType' => $mimeType,
                    'parents' => [$folderId],
                ])
                ->throw()
                ->json();
            $fileId = (string) ($created['id'] ?? '');
            if ($fileId === '') {
                throw new GoogleDriveStorageException('Google Drive tidak mengembalikan pengenal file bukti transfer.');
            }

            $contents = file_get_contents($file->getRealPath());
            if ($contents === false) {
                throw new GoogleDriveStorageException('File bukti transfer tidak dapat dibaca untuk diunggah.');
            }

            $uploaded = Http::timeout(30)
                ->acceptJson()
                ->withToken($token)
                ->withBody($contents, $mimeType)
                ->patch(self::UPLOAD_URL.'/'.$fileId.'?uploadType=media&fields=id,name,mimeType,size,createdTime')
                ->throw()
                ->json();

            return [
                'file_id' => $fileId,
                'metadata' => [
                    'name' => (string) ($uploaded['name'] ?? $name),
                    'mime_type' => (string) ($uploaded['mimeType'] ?? $mimeType),
                    'size' => (int) ($uploaded['size'] ?? $file->getSize()),
                    'uploaded_at' => (string) ($uploaded['createdTime'] ?? now()->toAtomString()),
                ],
            ];
        } catch (GoogleDriveStorageException $exception) {
            $this->deleteQuietly($fileId, $token);

            throw $exception;
        } catch (Throwable $exception) {
            $this->deleteQuietly($fileId, $token);

            throw new GoogleDriveStorageException('Bukti transfer gagal diunggah ke Google Drive. Silakan coba lagi.', 0, $exception);
        }
    }

    public function download(string $fileId): string
    {
        try {
            return Http::timeout(30)
                ->withToken($this->accessToken($this->credentials()))
                ->get(self::FILES_URL.'/'.$fileId.'?alt=media')
                ->throw()
                ->body();
        } catch (Throwable $exception) {
            throw new GoogleDriveStorageException('Bukti transfer tidak dapat diambil dari Google Drive.', 0, $exception);
        }
    }

    public function delete(string $fileId): void
    {
        try {
            Http::timeout(30)
                ->withToken($this->accessToken($this->credentials()))
                ->delete(self::FILES_URL.'/'.$fileId)
                ->throw();
        } catch (Throwable $exception) {
            throw new GoogleDriveStorageException('Bukti transfer tidak dapat dihapus dari Google Drive.', 0, $exception);
        }
    }

    /** @return array{client_email: string, private_key: string} */
    private function credentials(): array
    {
        $path = (string) config('services.google_drive.credentials');
        if ($path !== '' && ! str_starts_with($path, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            $path = base_path($path);
        }
        if ($path === '' || ! is_file($path)) {
            throw new GoogleDriveStorageException('Penyimpanan bukti transfer Google Drive belum dikonfigurasi.');
        }

        try {
            $credentials = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new GoogleDriveStorageException('Kredensial Google Drive tidak valid.', 0, $exception);
        }

        if (! is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new GoogleDriveStorageException('Kredensial Google Drive tidak lengkap.');
        }

        return [
            'client_email' => (string) $credentials['client_email'],
            'private_key' => (string) $credentials['private_key'],
        ];
    }

    /** @param array{client_email: string, private_key: string} $credentials */
    private function accessToken(array $credentials): string
    {
        $now = now()->timestamp;
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => self::DRIVE_SCOPE,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));
        $assertion = $header.'.'.$claims;
        if (! openssl_sign($assertion, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new GoogleDriveStorageException('Kredensial Google Drive tidak dapat digunakan.');
        }

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post(self::TOKEN_URL, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $assertion.'.'.$this->base64UrlEncode($signature),
                ])
                ->throw();
        } catch (Throwable $exception) {
            throw new GoogleDriveStorageException('Google Drive tidak dapat diautentikasi.', 0, $exception);
        }

        $token = (string) $response->json('access_token');
        if ($token === '') {
            throw new GoogleDriveStorageException('Google Drive tidak mengembalikan token akses.');
        }

        return $token;
    }

    private function deleteQuietly(?string $fileId, string $token): void
    {
        if (! $fileId) {
            return;
        }

        try {
            Http::timeout(15)->withToken($token)->delete(self::FILES_URL.'/'.$fileId);
        } catch (Throwable) {
            // An incomplete Drive upload must never make the payment transaction succeed.
        }
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
