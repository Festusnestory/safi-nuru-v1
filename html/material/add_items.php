<?php
if (!defined('NURU_ITEM_FORM_INCLUDE')) {
    http_response_code(404);
    exit('Not found');
}
?>
<div class="container py-5">

  <!-- ========================= -->
  <!-- ADMIN: MANAGE STAGES -->
  <!-- ========================= -->
 

  <!-- ========================= -->
  <!-- ADMIN: MANAGE ITEMS -->
  <!-- ========================= -->
  <div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-6">
      <div class="card">
        <div class="card-body">

          <h4 class="card-title">Manage Checklist Items</h4>
          <h5 class="card-subtitle mb-3 pb-3 border-bottom">
            Add tasks under a stage
          </h5>

          <form id="checklistItemForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($checklistConfigCsrf, ENT_QUOTES, 'UTF-8') ?>">

            <!-- Select Stage -->
            <div class="form-floating mb-3">
              <select
                id="checklist-stage"
                name="stage_id"
                class="form-select border border-success"
                required
              >
                <option value="">Select Stage</option>
                <!-- populate from DB -->
              </select>
              <label for="checklist-stage">
                <i data-feather="layers"
                   class="feather-sm text-success me-2"></i>
                <span class="border-start border-success ps-3">
                  Checklist Stage
                </span>
              </label>
            </div>

            <!-- Item Name -->
            <div class="form-floating mb-3">
              <input
                type="text"
                id="checklist-item-name"
                name="item_name"
                class="form-control border border-success"
                placeholder="Checklist Item"
                required
              />
              <label for="checklist-item-name">
                <i data-feather="check-square"
                   class="feather-sm text-success me-2"></i>
                <span class="border-start border-success ps-3">
                  Checklist Item
                </span>
              </label>
            </div>

            <!-- Item Order -->
            <div class="form-floating mb-3">
              <input
                type="number"
                id="checklist-item-order"
                name="item_order"
                class="form-control border border-success"
                placeholder="Item Order"
                min="1"
                required
              />
              <label for="checklist-item-order">
                <i data-feather="list"
                   class="feather-sm text-success me-2"></i>
                <span class="border-start border-success ps-3">
                  Item Order
                </span>
              </label>
            </div>

            <!-- Required -->
            <div class="form-check mb-3">
              <input
                type="checkbox"
                class="form-check-input"
                id="item_required"
                name="is_required"
                checked
              />
              <label class="form-check-label" for="item_required">
                This item is required to complete the stage
              </label>
            </div>

            <!-- Submit -->
            <div class="d-flex justify-content-end">
              <button
                type="submit"
                class="btn btn-success rounded-pill px-4"
              >
                <i data-feather="plus-circle" class="feather-sm me-2"></i>
                Add Checklist Item
              </button>
            </div>

            <div id="itemMessage" role="status" aria-live="polite"></div>

          </form>
        </div>
      </div>
    </div>
  </div>

</div>
