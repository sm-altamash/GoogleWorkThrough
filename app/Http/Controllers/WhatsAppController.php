<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Twilio\TwiML\MessagingResponse;

class WhatsAppController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth'); // Restrict to authenticated users (except webhook)
    }

    // Send a WhatsApp message
    public function sendMessage(Request $request)
    {
        $request->validate([
            'to' => 'required|string',
            'message' => 'required|string',
        ]);

        $twilio = new Client(config('services.twilio.sid'), config('services.twilio.token'));

        try {
            $twilio->messages->create(
                'whatsapp:' . $request->to,
                [
                    'from' => config('services.twilio.whatsapp_number'),
                    'body' => $request->message
                ]
            );

            Message::create([
                'user_id' => auth()->id(),
                'from' => str_replace('whatsapp:', '', config('services.twilio.whatsapp_number')),
                'to' => $request->to,
                'body' => $request->message,
                'direction' => 'outbound'
            ]);

            return redirect()->back()->with('success', 'Message sent successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // Handle incoming WhatsApp messages (webhook)
    public function webhook(Request $request)
    {
        $from = $request->input('From');
        $body = $request->input('Body');

        Message::create([
            'from' => str_replace('whatsapp:', '', $from),
            'to' => str_replace('whatsapp:', '', config('services.twilio.whatsapp_number')),
            'body' => $body,
            'direction' => 'inbound'
        ]);

        $response = new MessagingResponse();
        $response->message('Thanks for your message!');
        return response($response)->header('Content-Type', 'text/xml');
    }

    // Show list of conversations
    public function index()
    {
        $conversations = Message::select('from', 'to')
            ->groupBy('from', 'to')
            ->get()
            ->map(function ($message) {
                return $message->from === str_replace('whatsapp:', '', config('services.twilio.whatsapp_number'))
                    ? $message->to
                    : $message->from;
            })->unique();

        return view('admin.whatsapp.index', compact('conversations'));
    }

    // Show a specific conversation
    public function show($number)
    {
        $messages = Message::where(function ($query) use ($number) {
            $query->where('from', $number)
                  ->orWhere('to', $number);
        })->orderBy('created_at')->get();

        return view('admin.whatsapp.index', compact('messages', 'number'));
    }
}