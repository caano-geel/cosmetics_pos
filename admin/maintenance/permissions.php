<?php
if(admin_is_cashier()){
    echo "<script>alert('Access Denied!');location.replace('".admin_cashier_denied_redirect_url()."');</script>";
    return;
}

$catalog = admin_permission_catalog();
$cashier_perms = admin_load_cashier_permissions();
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-shield mr-1"></i> Role Permissions</h3>
    </div>
    <div class="card-body">
        <div id="perm-msg"></div>
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card card-outline card-secondary h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Admin/Owner</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Full access to all admin features. Permissions are fixed.</p>
                        <div class="permission-list">
                            <?php foreach($catalog as $key => $meta): ?>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input" id="perm-admin-<?php echo $key ?>" checked disabled>
                                <label class="custom-control-label" for="perm-admin-<?php echo $key ?>">
                                    <?php echo htmlspecialchars($meta['label']) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card card-outline card-info h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Cashier/Shopkeeper</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Choose what cashiers can access and do in the admin panel.</p>
                        <form id="cashier-permissions-form">
                            <div class="permission-list">
                                <?php foreach($catalog as $key => $meta): ?>
                                <?php $locked = !empty($meta['admin_only']); ?>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox"
                                        class="custom-control-input cashier-perm"
                                        name="permissions[<?php echo $key ?>]"
                                        id="perm-cashier-<?php echo $key ?>"
                                        value="1"
                                        data-perm="<?php echo $key ?>"
                                        <?php echo !empty($cashier_perms[$key]) ? 'checked' : '' ?>
                                        <?php echo $locked ? 'disabled' : '' ?>>
                                    <label class="custom-control-label" for="perm-cashier-<?php echo $key ?>">
                                        <?php echo htmlspecialchars($meta['label']) ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-sm btn-primary" id="save-cashier-permissions">
                            <i class="fas fa-save"></i> Save Permissions
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <p class="text-muted small mb-0">
            User roles are assigned via <code>users.type</code> (1 = Admin/Owner, 2 = Cashier/Shopkeeper). This page controls what cashiers are allowed to do.
        </p>
    </div>
</div>
<script>
$(function(){
    $('#save-cashier-permissions').click(function(){
        var perms = {};
        $('#cashier-permissions-form .cashier-perm').each(function(){
            perms[$(this).data('perm')] = $(this).is(':checked') ? 1 : 0;
        });
        start_loader();
        $.ajax({
            url: _base_url_ + 'classes/Master.php?f=save_cashier_permissions',
            method: 'POST',
            data: { permissions: JSON.stringify(perms) },
            dataType: 'json',
            error: function(){
                alert_toast('An error occurred while saving permissions.', 'error');
                end_loader();
            },
            success: function(resp){
                end_loader();
                if(resp && resp.status === 'success'){
                    alert_toast(resp.msg || 'Permissions saved.', 'success');
                }else{
                    alert_toast((resp && resp.msg) ? resp.msg : 'Unable to save permissions.', 'error');
                }
            }
        });
    });
});
</script>
