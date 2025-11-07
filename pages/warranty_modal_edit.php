<?php // pages/warranty_modal_edit.php ?>
<div class="modal fade" id="modalWarrantyEdit" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title">Edit Warranty</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                style="outline:none;border:0;background:transparent;font-size:28px;line-height:1;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="app/action/warranty_save.php" method="post" autocomplete="off">
        <div class="modal-body">
          <input type="hidden" name="mode" value="edit">
          <input type="hidden" name="id" id="e_id" value="">
          <div class="form-group">
            <label>Asset</label>
            <select name="asset_id" id="e_asset" class="form-control select2" required>
              <option value="">Select asset</option>
              <?php foreach ($assets as $a): ?>
                <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['product_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Vendor</label>
            <input type="text" name="vendor_name" id="e_vendor" class="form-control" placeholder="e.g. Dell, Lenovo, etc.">
          </div>
          <div class="form-group">
            <label>Start / End</label>
            <div class="d-flex">
              <input type="text"
                    name="start_date" id="e_start"
                    class="form-control mr-2 datepicker"
                    placeholder="YYYY-MM-DD"
                    inputmode="numeric"
                    pattern="^\d{4}-\d{2}-\d{2}$"
                    title="Use format YYYY-MM-DD"
                    autocomplete="off"
                    required>

              <input type="text"
                    name="end_date" id="e_end"
                    class="form-control datepicker"
                    placeholder="YYYY-MM-DD"
                    inputmode="numeric"
                    pattern="^\d{4}-\d{2}-\d{2}$"
                    title="Use format YYYY-MM-DD"
                    autocomplete="off"
                    required>
            </div>
            <small class="text-muted">Format: <code>YYYY-MM-DD</code></small>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Save changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
