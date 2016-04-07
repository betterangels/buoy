<noscript>
    <div class="notice error">
        <p><?php esc_html_e('To access the incident chat room, JavaScript must be enabled in your browser.', 'buoy');?></p>
    </div>
</noscript>
<script src="https://meet.jit.si/external_api.js"></script>
<style scoped="scoped">
    #jitsiConference0 { height: 100%; }
</style>
<script>
    var JitsiMeetAPI = new JitsiMeetExternalAPI(
        'meet.jit.si',
        '<?php print str_replace('_', '', esc_js($alert->get_chat_room_name()));?>',
        '100%',
        '100%'
    );
    // Not sure why this isn't working.
    // See the API docs: https://github.com/jitsi/jitsi-meet/blob/master/doc/api.md
    JitsiMeetApi.executeCommand('displayName', ['<?php print esc_js($curr_user->display_name);?>']);
</script>
