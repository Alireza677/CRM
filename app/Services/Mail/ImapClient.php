<?php

namespace App\Services\Mail;

use App\Models\Mailbox;
use Illuminate\Support\Facades\Log;
use Webklex\IMAP\Facades\Client as Imap;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\IMAP as ImapProtocol;

class ImapClient
{
    public function connect(Mailbox $mailbox): ?Client
    {
        if (!$mailbox->is_active) {
            return null;
        }

        $password = $mailbox->password;
        if (empty($password)) {
            Log::warning('[MAIL][IMAP] Mailbox password missing', ['mailbox_id' => $mailbox->id]);

            return null;
        }

        $config = [
            'host'          => $mailbox->imap_host,
            'port'          => $mailbox->imap_port ?: 993,
            'protocol'      => 'imap',
            'encryption'    => $this->mapEncryption($mailbox->imap_encryption),
            'validate_cert' => true,
            'username'      => $mailbox->username ?: $mailbox->email_address,
            'password'      => $password,
            'timeout'       => 30,
            'options'       => [
                'fetch'       => ImapProtocol::FT_PEEK,
                'sequence'    => ImapProtocol::ST_UID,
                'fetch_body'  => true,
                'fetch_flags' => true,
                'fetch_order' => 'desc',
            ],
        ];

        try {
            $client = Imap::make($config);
            $client->connect();

            return $client;
        } catch (\Throwable $e) {
            Log::warning('[MAIL][IMAP] Connection failed', [
                'mailbox_id' => $mailbox->id,
                'error'      => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getFolder(Client $client, string $path = 'INBOX'): ?Folder
    {
        try {
            $folder = $client->getFolder($path, delimiter: null, utf7: true);

            if (!$folder && $path !== 'INBOX') {
                $folder = $client->getFolder('INBOX', delimiter: null, utf7: true);
            }

            return $folder;
        } catch (\Throwable $e) {
            Log::warning('[MAIL][IMAP] Unable to get folder', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function mapEncryption(?string $encryption): string|bool
    {
        return match ($encryption) {
            'ssl'       => 'ssl',
            'tls'       => 'tls',
            'starttls'  => 'starttls',
            default     => false,
        };
    }
}
