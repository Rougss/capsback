<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Message;
use App\Services\WhatsAppService;

class SendScheduledMessages extends Command
{
    protected $signature = 'messages:send-scheduled';
    protected $description = 'Envoyer les messages planifiés';

    public function handle(WhatsAppService $whatsappService)
    {
        $messages = Message::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        $this->info("📨 {$messages->count()} messages à envoyer...");

        foreach ($messages as $message) {
            $client = $message->client;
            
            if (!$client || !$client->phone) {
                $message->update([
                    'status' => 'failed',
                    'error_message' => 'Numéro manquant'
                ]);
                continue;
            }

            $salonName = $message->user->salon_name ?? 'CapsBeauty';
            $fullMessage = "🌟 *{$salonName}*\n\n{$message->message}";

            $result = $whatsappService->sendMessage($client->phone, $fullMessage);

            if ($result['success']) {
                $message->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'whatsapp_message_id' => $result['message_id']
                ]);
                $this->info("✅ Message envoyé à {$client->name}");
            } else {
                $message->update([
                    'status' => 'failed',
                    'error_message' => $result['error']
                ]);
                $this->error("❌ Échec pour {$client->name}: {$result['error']}");
            }
        }

        $this->info("✨ Terminé !");
    }
}