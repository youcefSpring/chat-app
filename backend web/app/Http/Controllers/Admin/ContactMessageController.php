<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of contact messages.
     */
    public function index(Request $request): View
    {
        $query = ContactMessage::query();

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('subject', 'like', '%' . $request->search . '%')
                  ->orWhere('message', 'like', '%' . $request->search . '%');
            });
        }

        $messages = $query->latest()->paginate(15);

        // Status counts for quick filters
        $statusCounts = [
            'all' => ContactMessage::count(),
            'unread' => ContactMessage::unread()->count(),
            'read' => ContactMessage::read()->count(),
            'replied' => ContactMessage::replied()->count(),
            'spam' => ContactMessage::spam()->count(),
        ];

        return view('admin.contact.index', compact('messages', 'statusCounts'));
    }

    /**
     * Display the specified contact message.
     */
    public function show(ContactMessage $contactMessage): View
    {
        // Mark as read if unread
        if ($contactMessage->isUnread()) {
            $contactMessage->markAsRead();
        }

        return view('admin.contact.show', compact('contactMessage'));
    }

    /**
     * Update the status of the specified message.
     */
    public function updateStatus(Request $request, ContactMessage $contactMessage): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:unread,read,replied,spam'
        ]);

        $contactMessage->update(['status' => $request->status]);

        $statusMessages = [
            'unread' => 'Message marked as unread.',
            'read' => 'Message marked as read.',
            'replied' => 'Message marked as replied.',
            'spam' => 'Message marked as spam.',
        ];

        return redirect()->back()
            ->with('success', $statusMessages[$request->status]);
    }

    /**
     * Remove the specified contact message.
     */
    public function destroy(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->delete();

        return redirect()->route('admin.contact.index')
            ->with('success', 'Message deleted successfully.');
    }

    /**
     * Bulk update status for multiple messages.
     */
    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'exists:contact_messages,id',
            'status' => 'required|in:unread,read,replied,spam'
        ]);

        ContactMessage::whereIn('id', $request->message_ids)
            ->update(['status' => $request->status]);

        $count = count($request->message_ids);
        return redirect()->back()
            ->with('success', "{$count} messages updated successfully.");
    }

    /**
     * Bulk delete multiple messages.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'exists:contact_messages,id'
        ]);

        ContactMessage::whereIn('id', $request->message_ids)->delete();

        $count = count($request->message_ids);
        return redirect()->back()
            ->with('success', "{$count} messages deleted successfully.");
    }
}