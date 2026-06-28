<?php
$notifications = notifications_list(50);
?>
<div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Notification History</h3>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="markAllReadBtn">Mark All as Read</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" id="notif-history-table">
                <thead class="thead-light">
                    <tr>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($notifications) > 0): foreach($notifications as $n): ?>
                    <tr data-id="<?php echo (int)$n['id'] ?>">
                        <td class="text-nowrap"><?php echo date('Y-m-d H:i', strtotime($n['date_created'])) ?></td>
                        <td><span class="badge badge-<?php echo htmlspecialchars($n['type']) ?>"><?php echo htmlspecialchars(ucfirst($n['type'])) ?></span></td>
                        <td><?php echo htmlspecialchars(stripslashes($n['title'])) ?></td>
                        <td><?php echo htmlspecialchars(stripslashes($n['message'])) ?></td>
                        <td><?php echo (int)$n['is_read'] ? '<span class="text-muted">Read</span>' : '<span class="font-weight-bold text-primary">Unread</span>' ?></td>
                        <td>
                            <?php if(!(int)$n['is_read']): ?><button type="button" class="btn btn-xs btn-outline-primary mark-read-btn" data-id="<?php echo (int)$n['id'] ?>">Read</button><?php endif; ?>
                            <button type="button" class="btn btn-xs btn-outline-danger delete-notif-btn" data-id="<?php echo (int)$n['id'] ?>">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">No notifications yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
$(function(){
    $('#markAllReadBtn').click(function(){
        $.post(_base_url_+'classes/Master.php?f=mark_all_notifications_read', {}, function(){ location.reload(); }, 'json');
    });
    $('.mark-read-btn').click(function(){
        var id = $(this).data('id');
        $.post(_base_url_+'classes/Master.php?f=mark_notification_read', {id:id}, function(){ location.reload(); }, 'json');
    });
    $('.delete-notif-btn').click(function(){
        var id = $(this).data('id');
        _conf('Delete this notification?','delete_notif_row',[id]);
    });
    $('#notif-history-table').dataTable({ order:[[0,'desc']], columnDefs:[{orderable:false,targets:[5]}] });
});
function delete_notif_row(id){
    $.post(_base_url_+'classes/Master.php?f=delete_notification', {id:id}, function(){ location.reload(); }, 'json');
}
</script>
