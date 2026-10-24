<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PageController extends Controller
{
    public function about()
    {
        return view('pages.about');
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function contactSubmit(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ], [
            'name.required' => __('Indiquez votre nom.'),
            'email.required' => __('Indiquez votre adresse e-mail.'),
            'email.email' => __('L\'adresse e-mail doit être valide.'),
            'subject.required' => __('Indiquez le sujet de votre message.'),
            'message.required' => __('Écrivez votre message.'),
        ]);

        // Stored first: the message must never be lost, whatever happens to the e-mail
        $message = ContactMessage::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'subject' => $validated['subject'],
            'message' => $validated['message'].(! empty($validated['phone']) ? "\n\nTéléphone : ".$validated['phone'] : ''),
            'ip_address' => $request->ip(),
        ]);

        $to = getSetting('contact_email') ?: config('mail.from.address');

        try {
            Mail::raw(
                "De : {$message->name} <{$message->email}>\nSujet : {$message->subject}\n\n{$message->message}",
                fn ($mail) => $mail->to($to)->replyTo($message->email, $message->name)->subject('Contact : '.$message->subject)
            );
        } catch (Throwable $e) {
            report($e);
        }

        return redirect()->route('pages.contact')->with('success', __('Merci pour votre message ! Nous vous répondrons dans les plus brefs délais.'));
    }

    public function faq()
    {
        return view('pages.faq');
    }

    public function privacy()
    {
        return view('pages.privacy');
    }

    public function terms()
    {
        return view('pages.terms');
    }
}
