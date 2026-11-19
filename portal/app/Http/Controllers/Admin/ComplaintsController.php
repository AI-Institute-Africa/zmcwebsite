<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ComplaintsController extends Controller
{
    public function __construct() { $this->middleware(['auth']); }

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('receive_complaints_appeals') || auth()->user()->can('manage_complaints_appeals'), 403);
        $only = $request->route()?->defaults['only'] ?? null;
        $type = in_array($only, ['complaint','appeal'], true) ? $only : $request->get('type');
        $status = $request->get('status');
        $base = Complaint::query()->when(in_array($type,['complaint','appeal'],true), fn($q)=>$q->where('type',$type));
        $statsBase = clone $base;
        $stats = [
            'total'=>$statsBase->count(),
            'new'=>(clone $statsBase)->where('status','open')->count(),
            'active'=>(clone $statsBase)->where('status','in_progress')->count(),
            'resolved'=>(clone $statsBase)->whereIn('status',['resolved','closed'])->count(),
            'overdue'=>(clone $statsBase)->whereNotIn('status',['resolved','closed'])->whereNotNull('response_due_at')->where('response_due_at','<',now())->count(),
        ];
        $q=clone $base;
        if ($request->filled('search')) { $s=trim($request->input('search')); $q->where(fn($x)=>$x->where('full_name','like',"%{$s}%")->orWhere('email','like',"%{$s}%")->orWhere('subject','like',"%{$s}%")->orWhere('message','like',"%{$s}%")->orWhere('id',$s)); }
        if (in_array($status,['open','in_progress','resolved','closed'],true)) $q->where('status',$status);
        if (in_array($request->get('priority'),['low','normal','high','urgent'],true)) $q->where('priority',$request->get('priority'));
        if ($request->get('assigned')==='unassigned') $q->whereNull('handled_by'); elseif (is_numeric($request->get('assigned'))) $q->where('handled_by',(int)$request->get('assigned'));
        if ($request->filled('from')) $q->whereDate('created_at','>=',$request->get('from'));
        if ($request->filled('to')) $q->whereDate('created_at','<=',$request->get('to'));
        $items=$q->with('handler')->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END")->orderByDesc('created_at')->paginate(20)->withQueryString();
        $officers=User::role('complaints_officer')->orderBy('name')->get(['id','name']);
        $priorityStats=[]; foreach(['urgent','high','normal','low'] as $priority) $priorityStats[$priority]=(clone $statsBase)->where('priority',$priority)->whereNotIn('status',['resolved','closed'])->count();
        $section=$type==='appeal'?'appeals':($type==='complaint'?'complaints':'all');
        $heading=$section==='appeals'?'Appeals':($section==='complaints'?'Complaints':'Complaints & Appeals');
        $subheading=$section==='appeals'?'Formal appeals submitted via the website.':($section==='complaints'?'Public complaints submitted via the website.':'Received from the website.');
        $lockType=in_array($only,['complaint','appeal'],true);
        $updateRouteName=$section==='appeals'?'admin.appeals.update':'admin.complaints.update';
        $resetRouteName=$section==='appeals'?'admin.appeals.index':'admin.complaints.index';
        return view('admin.complaints.index',compact('items','type','status','section','heading','subheading','lockType','updateRouteName','resetRouteName','stats','officers','priorityStats'));
    }

    public function show(Complaint $complaint) { abort_unless(auth()->user()->can('receive_complaints_appeals') || auth()->user()->can('manage_complaints_appeals'),403); $complaint->load(['handler','communications.sender']); $officers=User::role('complaints_officer')->orderBy('name')->get(['id','name']); return view('admin.complaints.show',compact('complaint','officers')); }
    public function note(Request $request, Complaint $complaint) { abort_unless(auth()->user()->can('manage_complaints_appeals'),403); $data=$request->validate(['internal_notes'=>['nullable','string','max:10000']]); $complaint->update(['internal_notes'=>$data['internal_notes']??null]); \App\Support\AuditTrail::log('complaint_note_updated',null,['complaint_id'=>$complaint->id]); return back()->with('success','Internal notes saved.'); }
    public function reply(Request $request, Complaint $complaint) { abort_unless(auth()->user()->can('manage_complaints_appeals'),403); $data=$request->validate(['subject'=>['required','string','max:255'],'body'=>['required','string','max:10000']]); try { Mail::raw($data['body'],fn($mail)=>$mail->to($complaint->email)->subject($data['subject'])); if(method_exists($complaint,'communications')) $complaint->communications()->create(['sender_id'=>Auth::id(),'subject'=>$data['subject'],'body'=>$data['body'],'delivery_status'=>'sent','sent_at'=>now()]); $complaint->update(['first_responded_at'=>$complaint->first_responded_at?:now(),'status'=>$complaint->status==='open'?'in_progress':$complaint->status]); \App\Support\AuditTrail::log('complaint_reply_sent',null,['complaint_id'=>$complaint->id]); return back()->with('success','Reply sent and recorded.'); } catch(\Throwable $e) { report($e); return back()->with('error','The reply could not be sent.'); } }
    public function export(Request $request) { abort_unless(auth()->user()->can('receive_complaints_appeals') || auth()->user()->can('manage_complaints_appeals'),403); $rows=Complaint::where('type','complaint')->latest()->get(); return response()->streamDownload(function()use($rows){$out=fopen('php://output','w');fputcsv($out,['Reference','Date','Name','Email','Subject','Status','Priority','Assigned','Due']);foreach($rows as $c)fputcsv($out,['COMP-'.str_pad($c->id,6,'0',STR_PAD_LEFT),$c->created_at?->toDateTimeString(),$c->full_name,$c->email,$c->subject,$c->status,$c->priority,$c->handled_by,$c->response_due_at?->toDateTimeString()]);fclose($out);},'complaints-'.now()->format('Y-m-d').'.csv',['Content-Type'=>'text/csv']); }

    public function update(Request $request, Complaint $complaint)
    {
        abort_unless(auth()->user()->can('manage_complaints_appeals'), 403);
        $data=$request->validate(['status'=>['required','in:open,in_progress,resolved,closed'],'priority'=>['required','in:low,normal,high,urgent'],'assigned_to'=>['nullable','integer','exists:users,id'],'response_due_at'=>['nullable','date']]);
        $oldStatus=$complaint->status;
        $complaint->update(['status'=>$data['status'],'priority'=>$data['priority'],'handled_by'=>$data['assigned_to'] ?: Auth::id(),'response_due_at'=>$data['response_due_at'] ?: null,'handled_at'=>in_array($data['status'],['resolved','closed'],true)?now():$complaint->handled_at,'first_responded_at'=>($oldStatus==='open' && $data['status']!=='open' && !$complaint->first_responded_at)?now():$complaint->first_responded_at]);
        \App\Support\AuditTrail::log('complaint_update',null,['complaint_id'=>$complaint->id,'status'=>$data['status'],'priority'=>$data['priority'],'assigned_to'=>$data['assigned_to'] ?? null]);
        if ($oldStatus!==$data['status'] && filter_var($complaint->email,FILTER_VALIDATE_EMAIL)) {
            try { Mail::raw("Your ZMC complaint (COMP-".str_pad($complaint->id,6,'0',STR_PAD_LEFT).") status has been updated to: ".str_replace('_',' ',$data['status']).".\n\nOur team will contact you with further information.", fn($mail)=>$mail->to($complaint->email)->subject('ZMC complaint status update')); } catch (\Throwable $e) { report($e); }
        }
        return back()->with('success','Complaint workflow updated.');
    }
}
