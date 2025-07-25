<?php

namespace App\Services;

use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Google\Service\Gmail\Draft;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class GoogleGmailService
{
    protected GoogleClientService $googleClientService;

    public function __construct(GoogleClientService $googleClientService)
    {
        $this->googleClientService = $googleClientService;
    }

    protected function getGmailService(User $user): Gmail
    {
        $client = $this->googleClientService->getClientForUser($user);
        return new Gmail($client);
    }

    public function listEmails(User $user, int $maxResults = 10): array
    {
        try {
            $service = $this->getGmailService($user);
            $results = $service->users_messages->listUsersMessages('me', [
                'maxResults' => $maxResults,
                'labelIds' => ['INBOX'],
                'fields' => 'messages(id,threadId,snippet)'
            ]);

            $messages = [];
            foreach ($results->getMessages() as $message) {
                $fullMessage = $service->users_messages->get('me', $message->getId(), ['format' => 'full']);
                $headers = $fullMessage->getPayload()->getHeaders();

                $messages[] = [
                    'id' => $message->getId(),
                    'threadId' => $message->getThreadId(),
                    'subject' => $this->getHeaderValue($headers, 'Subject'),
                    'from' => $this->getHeaderValue($headers, 'From'),
                    'date' => $this->getHeaderValue($headers, 'Date'),
                    'snippet' => $message->getSnippet()
                ];
            }

            return $messages;

        } catch (\Exception $e) {
            Log::error('Failed to list Gmail messages', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function sendEmail(User $user, string $to, string $subject, string $body, array $options = []): ?Message
    {
        try {
            $service = $this->getGmailService($user);
            $message = $this->createMessage($user->email, $to, $subject, $body, $options);
            
            return $service->users_messages->send('me', $message);

        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'user_id' => $user->id,
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function createDraft(User $user, string $to, string $subject, string $body, array $options = []): ?Draft
    {
        try {
            $service = $this->getGmailService($user);
            $message = $this->createMessage($user->email, $to, $subject, $body, $options);
            
            $draft = new Draft();
            $draft->setMessage($message);
            
            return $service->users_drafts->create('me', $draft);

        } catch (\Exception $e) {
            Log::error('Failed to create draft', [
                'user_id' => $user->id,
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    
    public function isConnected(User $user): bool
    {
        try {
            $service = $this->getGmailService($user);
            $profile = $service->users->getProfile('me');
            return !empty($profile->getEmailAddress());
        } catch (\Exception $e) {
            return false;
        }
    }


    protected function createMessage(string $from, string $to, string $subject, string $body, array $options = []): Message
    {
        $message = new Message();
        
        $rawMessageString = "From: <{$from}>\r\n";
        $rawMessageString .= "To: <{$to}>\r\n";
        $rawMessageString .= 'Subject: =?utf-8?B?' . base64_encode($subject) . "?=\r\n";
        $rawMessageString .= "MIME-Version: 1.0\r\n";
        
        // Handle attachments if present
        if (!empty($options['attachments'])) {
            $boundary = uniqid('boundary_');
            $rawMessageString .= "Content-Type: multipart/mixed; boundary={$boundary}\r\n\r\n";
            $rawMessageString .= "--{$boundary}\r\n";
            $rawMessageString .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
            $rawMessageString .= $body . "\r\n\r\n";

            foreach ($options['attachments'] as $attachment) {
                $rawMessageString .= "--{$boundary}\r\n";
                $rawMessageString .= "Content-Type: " . $attachment['type'] . "\r\n";
                $rawMessageString .= "Content-Transfer-Encoding: base64\r\n";
                $rawMessageString .= "Content-Disposition: attachment; filename=" . $attachment['name'] . "\r\n\r\n";
                $rawMessageString .= chunk_split(base64_encode($attachment['content'])) . "\r\n";
            }
            $rawMessageString .= "--{$boundary}--";
        } else {
            $rawMessageString .= "Content-Type: text/html; charset=utf-8\r\n";
            $rawMessageString .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $rawMessageString .= base64_encode($body);
        }

        $rawMessage = strtr(base64_encode($rawMessageString), array('+' => '-', '/' => '_'));
        $message->setRaw($rawMessage);

        return $message;
    }

    protected function getHeaderValue(array $headers, string $name): ?string
    {
        foreach ($headers as $header) {
            if ($header->name === $name) {
                return $header->value;
            }
        }
        return null;
    }
}