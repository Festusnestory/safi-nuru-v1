<?php
if (!defined('NURU_STAGE_FORM_INCLUDE')) {
    http_response_code(404);
    exit('Not found');
}
?>
<div class="container py-5">

  <!-- ========================= -->
  <!-- ADMIN: MANAGE STAGES -->
  <!-- ========================= -->
  <div class="row justify-content-center mb-5">
    <div class="col-12 col-md-10 col-lg-6">
      <div class="card">
        <div class="card-body">

          <h4 class="card-title">Manage Checklist Stages</h4>
          <h5 class="card-subtitle mb-3 pb-3 border-bottom">
            Define workflow steps
          </h5>

          <form id="stageForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($checklistConfigCsrf, ENT_QUOTES, 'UTF-8') ?>">

            <!-- Stage Name -->
            <div class="form-floating mb-3">
              <input
                type="text"
                id="stage-name"
                name="stage_name"
                class="form-control border border-success"
                placeholder="Stage Name"
                required
              />
              <label for="stage-name">
                <i data-feather="layers"
                   class="feather-sm text-success me-2"></i>
                <span class="border-start border-success ps-3">
                  Stage Name
                </span>
              </label>
            </div>

            <!-- Stage Order -->
            <div class="form-floating mb-3">
              <input
                type="number"
                id="stage-order"
                name="stage_order"
                class="form-control border border-success"
                placeholder="Stage Order"
                min="1"
                required
              />
              <label for="stage-order">
                <i data-feather="list"
                   class="feather-sm text-success me-2"></i>
                <span class="border-start border-success ps-3">
                  Stage Order
                </span>
              </label>
            </div>

            <!-- Description -->
            <div class="form-floating mb-3">
              <textarea
                id="stage-description"
                name="description"
                class="form-control border border-success"
                placeholder="Description"
                style="height: 120px"
              ></textarea>
              <label for="stage-description">
                <i data-feather="file-text"
                   class="feather-sm text-success me-2"></i>
                <span class="border-start border-success ps-3">
                  Description (optional)
                </span>
              </label>
            </div>

            <!-- Active -->
            <div class="form-check mb-3">
              <input
                type="checkbox"
                class="form-check-input"
                id="stage_active"
                name="is_active"
                checked
              />
              <label class="form-check-label" for="stage_active">
                Stage is active
              </label>
            </div>

            <!-- Submit -->
            <div class="d-flex justify-content-end">
              <button
                type="submit"
                class="btn btn-success rounded-pill px-4"
              >
                <i data-feather="save" class="feather-sm me-2"></i>
                Save Stage
              </button>
            </div>

            <div id="stageMessage" role="status" aria-live="polite"></div>

          </form>
        </div>
      </div>
    </div>
  </div>


</div>
