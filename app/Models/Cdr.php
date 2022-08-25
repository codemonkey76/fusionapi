<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cdr extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'v_xml_cdr';
    protected $hidden = [
        'json', 'last_arg', 'default_language', 'context',
        'bridge_uuid', 'read_codec', 'read_rate', 'write_codec', 'write_rate', 'remote_media_ip',
        'network_addr', 'record_path', 'record_name', 'leg', 'ppd_ms', 'rtp_audio_in_mos', 'last_app',
        'cc_side', 'cc_member_uuid', 'cc_queue_joined_epoch', 'cc_queue', 'cc_member_session_uuid',
        'cc_agent_uuid', 'cc_agent', 'cc_agent_type', 'cc_agent_bridged', 'cc_queue_answered_epoch',
        'cc_queue_terminated_epoch', 'cc_queue_canceled_epoch', 'cc_cancel_reason', 'cc_cause',
        'conference_name', 'conference_uuid', 'conference_member_id', 'digits_dialed', 'pin_number',
        'hangup_cause_q850', 'xml', 'sip_call_id', 'originating_leg_uuid', 'voicemail_message',
        'call_center_queue_uuid'
    ];
}
