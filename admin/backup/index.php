<?php
require_once __DIR__.'/../inc/module_ui.php';
if(admin_is_cashier()){
    echo '<div class="alert alert-danger">Access denied. Backup & Restore is available to administrators only.</div>';
    return;
}
$backup_dir = backup_dir_path();
$backup_count = 0;
$backup_storage = 0;
$last_backup_date = '&mdash;';
if(backup_logs_table_enabled()){
    $stats_row = $conn->query("SELECT COUNT(*) AS total, COALESCE(SUM(file_size), 0) AS storage FROM backup_logs")->fetch_assoc();
    $backup_count = (int)($stats_row['total'] ?? 0);
    $backup_storage = (float)($stats_row['storage'] ?? 0);
    $last_backup = backup_last_info();
    if($last_backup) $last_backup_date = date('M d, Y H:i', strtotime($last_backup['date_created']));
}
echo module_page_styles();
?>
<?php if($_settings->chk_flashdata('success')): ?>
<script>alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')</script>
<?php endif; ?>
<div class="mod-page">
    <div class="card mod-header mod-header-backup">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
            <div class="mb-2 mb-md-0">
                <h4><i class="fas fa-database mr-2"></i>Backup & Restore</h4>
                <p class="mod-subtitle">Create, download, and restore database backups safely</p>
            </div>
            <?php if(backup_logs_table_enabled()): ?>
            <button type="button" class="btn mod-btn-action mod-btn-success" id="createBackupBtn">
                <i class="fas fa-cloud-download-alt mr-1"></i> One-Click Backup
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if(!backup_logs_table_enabled()): ?>
    <div class="alert alert-warning">Backup module not installed. Run <code>database/update_new_modules.sql</code>.</div>
    <?php else: ?>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-backup"><i class="fas fa-info-circle"></i> Overview</div>
        <div class="mod-section-body">
            <div class="row">
                <?php
                echo module_mini_stat('Total Backups', format_num($backup_count), 'fa-archive', 'bg-info');
                echo module_mini_stat('Last Backup Date', $last_backup_date, 'fa-clock', 'bg-primary');
                echo module_mini_stat('Backup Storage Size', format_file_size($backup_storage), 'fa-hdd', 'bg-teal');
                ?>
            </div>
        </div>
    </div>

    <div class="mod-warning-card">
        <div class="mod-warning-title"><i class="fas fa-exclamation-triangle mr-1"></i> Restore Warning</div>
        <p>Restoring a backup will <strong>overwrite all current database data</strong>. This action cannot be undone. Only administrators can restore backups. Always download a fresh backup before restoring an older version.</p>
    </div>

    <div class="mod-section">
        <div class="mod-section-header mod-sh-backup"><i class="fas fa-history"></i> Backup History</div>
        <div class="mod-section-body p-0">
            <p class="small text-muted px-3 pt-2 mb-0">Files stored in <code>uploads/backups/</code></p>
            <div class="mod-table-wrap mt-2">
                <table class="table table-hover mod-table" id="backup-list">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>File Name</th>
                            <th>File Size</th>
                            <th>Date Created</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        $qry = $conn->query("SELECT * FROM backup_logs ORDER BY date_created DESC, id DESC");
                        if($qry):
                            while($row = $qry->fetch_assoc()):
                        ?>
                        <tr>
                            <td class="text-muted"><?php echo $i++ ?></td>
                            <td><span class="mod-badge-id"><i class="fas fa-file-code mr-1"></i><?php echo htmlspecialchars($row['filename']) ?></span></td>
                            <td><?php echo format_file_size($row['file_size']) ?></td>
                            <td class="text-nowrap"><?php echo date('Y-m-d H:i', strtotime($row['date_created'])) ?></td>
                            <td><?php echo htmlspecialchars($row['created_by_name'] !== '' ? $row['created_by_name'] : 'System') ?></td>
                            <td>
                                <?php if($row['status'] === 'success'): ?>
                                <span class="mod-badge-status badge-success">Success</span>
                                <?php else: ?>
                                <span class="mod-badge-status badge-danger"><?php echo htmlspecialchars($row['status']) ?></span>
                                <?php endif; ?>
                                <?php if(!empty($row['message'])): ?>
                                <div class="small text-muted mt-1"><?php echo htmlspecialchars(stripslashes($row['message'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-nowrap">
                                <a href="<?php echo base_url ?>classes/Master.php?f=download_backup&id=<?php echo (int)$row['id'] ?>"
                                   class="btn btn-outline-primary mod-action-btn mr-1" title="Download"><i class="fas fa-download"></i></a>
                                <button type="button" class="btn btn-outline-warning mod-action-btn restore-backup mr-1"
                                    data-id="<?php echo (int)$row['id'] ?>" data-name="<?php echo htmlspecialchars($row['filename']) ?>" title="Restore"><i class="fas fa-undo"></i></button>
                                <button type="button" class="btn btn-outline-danger mod-action-btn delete-backup"
                                    data-id="<?php echo (int)$row['id'] ?>" title="Delete"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if(!$qry || $qry->num_rows === 0): ?>
            <p class="text-muted text-center py-4 mb-0">No backups yet. Click <strong>One-Click Backup</strong> to create your first backup.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<script>
$(function(){
    $('#createBackupBtn').click(function(){
        _conf('Create a full database backup now?','run_create_backup',[]);
    });
    $('.restore-backup').click(function(){
        var id = $(this).data('id');
        var name = $(this).data('name');
        _conf('WARNING: Restore will overwrite ALL current data with backup "'+name+'". Continue?','run_restore_backup',[id]);
    });
    $('.delete-backup').click(function(){
        _conf('Delete this backup file permanently?','run_delete_backup',[$(this).data('id')]);
    });
    if($('#backup-list tbody tr').length) {
        $('#backup-list').dataTable({ order:[[3,'desc']], columnDefs:[{orderable:false,targets:[6]}] });
    }
});
function run_create_backup(){
    start_loader();
    $.post(_base_url_+'classes/Master.php?f=create_backup',{},function(resp){
        if(typeof resp==='object' && resp.status==='success') location.reload();
        else { alert_toast(resp.msg||'Backup failed','error'); end_loader(); }
    },'json');
}
function run_restore_backup(id){
    start_loader();
    $.post(_base_url_+'classes/Master.php?f=restore_backup',{id:id},function(resp){
        if(typeof resp==='object' && resp.status==='success'){ alert_toast(resp.msg,'success'); setTimeout(function(){ location.reload(); },1500); }
        else { alert_toast(resp.msg||'Restore failed','error'); end_loader(); }
    },'json');
}
function run_delete_backup(id){
    start_loader();
    $.post(_base_url_+'classes/Master.php?f=delete_backup',{id:id},function(resp){
        if(typeof resp==='object' && resp.status==='success') location.reload();
        else { alert_toast('Delete failed','error'); end_loader(); }
    },'json');
}
</script>
