<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Client;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Récupérer tous les messages de l'utilisateur
     */
    public function index(Request $request)
    {
        $query = Message::with('client')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        // Filtrer par statut si demandé
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $messages = $query->get();

        return response()->json($messages);
    }

    /**
     * Créer un nouveau message
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'message' => 'required|string',
            'type' => 'required|in:reminder,promotion,custom',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = $request->has('scheduled_at') ? 'scheduled' : 'draft';

        $message = Message::create($validated);

        // Si pas de date planifiée, envoyer immédiatement
        if (!$request->has('scheduled_at')) {
            $this->sendWhatsAppMessage($message);
        }

        return response()->json([
            'message' => $message->status === 'sent' 
                ? 'Message envoyé avec succès' 
                : 'Message créé avec succès',
            'data' => $message->load('client')
        ], 201);
    }

    /**
     * Envoyer un message via WhatsApp
     */
    protected function sendWhatsAppMessage(Message $message)
    {
        $client = Client::find($message->client_id);

        if (!$client || !$client->phone) {
            $message->update([
                'status' => 'failed',
                'error_message' => 'Numéro de téléphone manquant'
            ]);
            return;
        }

        // Construire le message avec le nom du salon
        $salonName = $message->user->salon_name ?? 'CapsBeauty';
        $fullMessage = "🌟 *{$salonName}*\n\n{$message->message}\n\n_Répondez OUI pour confirmer ou NON pour annuler_";

        // Envoyer via WhatsApp
        $result = $this->whatsappService->sendMessage(
            $client->phone,
            $fullMessage
        );

        if ($result['success']) {
            $message->update([
                'status' => 'sent',
                'sent_at' => now(),
                'whatsapp_message_id' => $result['message_id']
            ]);
        } else {
            $message->update([
                'status' => 'failed',
                'error_message' => $result['error']
            ]);
        }
    }

    /**
     * Envoyer un message maintenant
     */
    public function send(Request $request, $id)
    {
        $message = Message::where('user_id', $request->user()->id)
            ->findOrFail($id);

        if ($message->status === 'sent') {
            return response()->json([
                'message' => 'Ce message a déjà été envoyé'
            ], 400);
        }

        $this->sendWhatsAppMessage($message);

        return response()->json([
            'message' => $message->status === 'sent' 
                ? 'Message envoyé avec succès' 
                : 'Erreur lors de l\'envoi',
            'data' => $message->fresh()
        ]);
    }

    /**
     * Envoyer un message groupé
     */
    public function sendBulk(Request $request)
    {
        $validated = $request->validate([
            'client_ids' => 'required|array',
            'client_ids.*' => 'exists:clients,id',
            'message' => 'required|string',
            'type' => 'required|in:reminder,promotion,custom',
        ]);

        $results = [
            'success' => 0,
            'failed' => 0,
            'messages' => []
        ];

        foreach ($validated['client_ids'] as $clientId) {
            $message = Message::create([
                'user_id' => $request->user()->id,
                'client_id' => $clientId,
                'message' => $validated['message'],
                'type' => $validated['type'],
                'status' => 'draft'
            ]);

            $this->sendWhatsAppMessage($message);

            if ($message->fresh()->status === 'sent') {
                $results['success']++;
            } else {
                $results['failed']++;
            }

            $results['messages'][] = $message->fresh()->load('client');
        }

        return response()->json([
            'message' => "Messages envoyés : {$results['success']} succès, {$results['failed']} échecs",
            'data' => $results
        ]);
    }

    /**
     * Mettre à jour un message
     */
    public function update(Request $request, $id)
    {
        $message = Message::where('user_id', $request->user()->id)
            ->findOrFail($id);

        if ($message->status === 'sent') {
            return response()->json([
                'message' => 'Impossible de modifier un message déjà envoyé'
            ], 400);
        }

        $validated = $request->validate([
            'message' => 'sometimes|string',
            'type' => 'sometimes|in:reminder,promotion,custom',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $message->update($validated);

        return response()->json([
            'message' => 'Message mis à jour',
            'data' => $message->fresh()
        ]);
    }

    /**
     * Supprimer un message
     */
    public function destroy(Request $request, $id)
    {
        $message = Message::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $message->delete();

        return response()->json([
            'message' => 'Message supprimé'
        ]);
    }

    /**
     * Vérifier le statut d'un message
     */
    public function checkStatus(Request $request, $id)
    {
        $message = Message::where('user_id', $request->user()->id)
            ->findOrFail($id);

        if (!$message->whatsapp_message_id) {
            return response()->json([
                'message' => 'Aucun ID WhatsApp trouvé'
            ], 400);
        }

        $status = $this->whatsappService->getMessageStatus($message->whatsapp_message_id);

        return response()->json([
            'whatsapp_status' => $status,
            'message' => $message
        ]);
    }
}