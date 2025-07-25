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
        $this->middleware('auth')->except(['webhook']);
    }

    /** ---------------- Send message ---------------- */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'to'      => 'required|string',
            'message' => 'required|string',
        ]);

        try {
            /** ── Twilio outbound ─────────────────── */
            $twilio = new Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );

            $twilio->messages->create(
                'whatsapp:' . $request->to,
                [
                    'from' => config('services.twilio.whatsapp_number'),
                    'body' => $request->message,
                ]
            );

            /** ── Persist to DB ───────────────────── */
            $msg = Message::create([
                'user_id'   => auth()->id(),
                'from'      => str_replace('whatsapp:', '', config('services.twilio.whatsapp_number')),
                'to'        => $request->to,
                'body'      => $request->message,
                'direction' => 'outbound',
            ]);

            // AJAX? → JSON ; otherwise redirect
            if ($request->ajax()) {
                return response()->json($msg, 201);
            }

            return back()->with('success', 'Message sent!');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /** ---------------- Incoming webhook ---------------- */
    public function webhook(Request $request)
    {
        $msg = Message::create([
            'from'      => str_replace('whatsapp:', '', $request->input('From')),
            'to'        => str_replace('whatsapp:', '', config('services.twilio.whatsapp_number')),
            'body'      => $request->input('Body'),
            'direction' => 'inbound',
        ]);

        $twiml = new MessagingResponse();
        $twiml->message('Thanks for your message!');

        return response($twiml)->header('Content-Type', 'text/xml');
    }

    /** ---------------- Conversation list ---------------- */
    public function index()
    {
        $conversations = Message::select('from', 'to')
            ->groupBy('from', 'to')
            ->get()
            ->map(function ($m) {
                $bot = str_replace('whatsapp:', '', config('services.twilio.whatsapp_number'));
                return $m->from === $bot ? $m->to : $m->from;
            })
            ->unique()
            ->values();

        return view('admin.whatsapp.index', compact('conversations'));
    }

    /** ---------------- Single conversation ---------------- */
    public function show(Request $request, $number)
    {
        $messages = Message::where(function ($q) use ($number) {
                $q->where('from', $number)->orWhere('to', $number);
            })
            ->oldest()
            ->get();

        // AJAX? → JSON ; otherwise HTML
        if ($request->ajax()) {
            return response()->json($messages);
        }

        return view('admin.whatsapp.index', compact('messages', 'number'));
    }
}