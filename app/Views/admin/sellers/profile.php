<?php
/** @var array $application */
/** @var string $baseUrl */
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta
      name="keywords"
      content="Nuru real estate administration, property management, buyers, sellers, agents, tasks, and reporting"
    />
    <meta
      name="description"
      content="Admin Pro is powerful and clean admin dashboard template"
    />
    <meta name="robots" content="noindex,nofollow" />
    <title>Seller Application Profile</title>
    <!-- Favicon icon -->
    <link
      rel="icon"
      type="image/png"
      sizes="16x16"
      href="<?= $baseUrl ?>/assets/images/favicon.png"
    />
    <!-- Custom CSS -->
    <link href="<?= $baseUrl ?>/dist/css/style.min.css" rel="stylesheet" />
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <![endif]-->
  </head>

  <body>
    <!-- -------------------------------------------------------------- -->
    <!-- Preloader - style you can find in spinners.css -->
    <!-- -------------------------------------------------------------- -->
    <div class="preloader">
      <svg
        class="tea lds-ripple"
        width="37"
        height="48"
        viewbox="0 0 37 48"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          d="M27.0819 17H3.02508C1.91076 17 1.01376 17.9059 1.0485 19.0197C1.15761 22.5177 1.49703 29.7374 2.5 34C4.07125 40.6778 7.18553 44.8868 8.44856 46.3845C8.79051 46.79 9.29799 47 9.82843 47H20.0218C20.639 47 21.2193 46.7159 21.5659 46.2052C22.6765 44.5687 25.2312 40.4282 27.5 34C28.9757 29.8188 29.084 22.4043 29.0441 18.9156C29.0319 17.8436 28.1539 17 27.0819 17Z"
          stroke="#1e88e5"
          stroke-width="2"
        ></path>
        <path
          d="M29 23.5C29 23.5 34.5 20.5 35.5 25.4999C36.0986 28.4926 34.2033 31.5383 32 32.8713C29.4555 34.4108 28 34 28 34"
          stroke="#1e88e5"
          stroke-width="2"
        ></path>
        <path
          id="teabag"
          fill="#1e88e5"
          fill-rule="evenodd"
          clip-rule="evenodd"
          d="M16 25V17H14V25H12C10.3431 25 9 26.3431 9 28V34C9 35.6569 10.3431 37 12 37H18C19.6569 37 21 35.6569 21 34V28C21 26.3431 19.6569 25 18 25H16ZM11 28C11 27.4477 11.4477 27 12 27H18C18.5523 27 19 27.4477 19 28V34C19 34.5523 18.5523 35 18 35H12C11.4477 35 11 34.5523 11 34V28Z"
        ></path>
        <path
          id="steamL"
          d="M17 1C17 1 17 4.5 14 6.5C11 8.5 11 12 11 12"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke="#1e88e5"
        ></path>
        <path
          id="steamR"
          d="M21 6C21 6 21 8.22727 19 9.5C17 10.7727 17 13 17 13"
          stroke="#1e88e5"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        ></path>
      </svg>
    </div>
    <!-- -------------------------------------------------------------- -->
    <!-- Main wrapper - style you can find in pages.scss -->
    <!-- -------------------------------------------------------------- -->
    <div id="main-wrapper">
      <!-- -------------------------------------------------------------- -->
      <!-- Topbar header - style you can find in pages.scss -->
      <!-- -------------------------------------------------------------- -->
      <?php require NURU_MATERIAL . '/top-bar.php'; ?>
      <!-- -------------------------------------------------------------- -->
      <!-- End Topbar header -->
      <!-- -------------------------------------------------------------- -->
      <!-- -------------------------------------------------------------- -->
      <!-- Left Sidebar - style you can find in sidebar.scss  -->
      <!-- -------------------------------------------------------------- -->
      <?php
		if (\App\Core\Auth::isFullAccess()) {
			require NURU_MATERIAL . '/left-sidebar.php';
		} else {
			require NURU_MATERIAL . '/agent_nemu.php';
		}
	  ?>
      <!-- -------------------------------------------------------------- -->
      <!-- End Left Sidebar - style you can find in sidebar.scss  -->
      <!-- -------------------------------------------------------------- -->
      <!-- -------------------------------------------------------------- -->
      <!-- Page wrapper  -->
      <!-- -------------------------------------------------------------- -->
      <div class="page-wrapper">
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <div class="row page-titles">
          <div class="col-md-5 col-12 align-self-center">
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item">
                <a href="javascript:void(0)">Home</a>
              </li>
              <li class="breadcrumb-item active">Seller Application Profile</li>
            </ol>
          </div>
          <div class="col-md-7 col-12 align-self-center text-end">
            <div class="d-flex justify-content-end align-items-center">
              <span class="badge bg-<?= $application['status'] === 'approved' ? 'success' : ($application['status'] === 'rejected' ? 'danger' : 'warning') ?> fs-6 px-3 py-2">
                Status: <?= ucfirst($application['status']) ?>
              </span>
            </div>
          </div>
        </div>
        <!-- ============================================================== -->
        <!-- End Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->
        <!-- -------------------------------------------------------------- -->
        <!-- Container fluid  -->
        <!-- -------------------------------------------------------------- -->
        <div class="container-fluid">
          <!-- -------------------------------------------------------------- -->
          <!-- Start Page Content -->
          <!-- -------------------------------------------------------------- -->
          <!-- Row -->
          <div class="row">
            <!-- Column -->
            <div class="col-lg-4 col-xlg-3 col-md-5">
  <div class="card">
    <div class="card-body">

      <!-- Profile Image + Name -->
      <div class="text-center mb-4">
        <img src="<?= $baseUrl ?>/assets/images/users/5.jpg" class="rounded-circle mb-2" width="120" />
        <h4 class="card-title mb-0">
          <?= $application['personal_details']['first_name'] . ' ' . $application['personal_details']['surname'] ?>
        </h4>
      </div>

      <!-- PERSONAL DETAILS -->
      <h6 class="text-uppercase text-muted mb-2">Personal Details</h6>

      <div class="row mb-1">
        <div class="col-5 text-muted">Date of Birth</div>
        <div class="col-7"><?= $application['personal_details']['date_of_birth'] ?></div>
      </div>

      <div class="row mb-1">
        <div class="col-5 text-muted"><?= $application['personal_details']['id_type'] ?></div>
        <div class="col-7"><?= $application['personal_details']['id_number'] ?></div>
      </div>

      <div class="row mb-1">
        <div class="col-5 text-muted">Nationality</div>
        <div class="col-7"><?= $application['personal_details']['nationality'] ?></div>
      </div>

      <div class="row mb-1">
        <div class="col-5 text-muted">Gender</div>
        <div class="col-7"><?= $application['personal_details']['gender'] ?></div>
      </div>

      <div class="row mb-3">
        <div class="col-5 text-muted">Age</div>
        <div class="col-7"><?= $application['personal_details']['age'] ?></div>
      </div>

      <hr>

      <!-- MARITAL STATUS -->
      <h6 class="text-uppercase text-muted mb-2">Marital Status</h6>

      <div class="row mb-1">
        <div class="col-5 text-muted">Status</div>
        <div class="col-7"><?= $application['marital_status']['marital_status'] ?></div>
      </div>

      <?php if ($application['marital_status']['marital_status'] !== 'Single'): ?>
        <div class="row mb-1">
          <div class="col-5 text-muted">Spouse Name</div>
          <div class="col-7">
            <?= $application['marital_status']['spouse_first_name'] . ' ' . $application['marital_status']['spouse_surname'] ?>
          </div>
        </div>

        <div class="row mb-1">
          <div class="col-5 text-muted">Spouse DOB</div>
          <div class="col-7"><?= $application['marital_status']['spouse_date_of_birth'] ?></div>
        </div>

        <div class="row mb-1">
          <div class="col-5 text-muted"><?= $application['marital_status']['spouse_id_type'] ?></div>
          <div class="col-7"><?= $application['marital_status']['spouse_id_number'] ?></div>
        </div>

        <div class="row mb-3">
          <div class="col-5 text-muted">Nationality</div>
          <div class="col-7"><?= $application['marital_status']['spouse_nationality'] ?></div>
        </div>
      <?php endif; ?>

      <hr>

      <!-- NEXT OF KIN -->
      <h6 class="text-uppercase text-muted mb-2">Next of Kin</h6>

      <div class="row mb-1">
        <div class="col-5 text-muted">Name</div>
        <div class="col-7">
          <?= $application['next_of_kin']['nok_first_name'] . ' ' . $application['next_of_kin']['nok_surname'] ?>
        </div>
      </div>

      <div class="row mb-1">
        <div class="col-5 text-muted">Phone</div>
        <div class="col-7"><?= $application['next_of_kin']['nok_contact_number'] ?></div>
      </div>

      <div class="row mb-3">
        <div class="col-5 text-muted">Email</div>
        <div class="col-7"><?= $application['next_of_kin']['nok_email'] ?></div>
      </div>

      <hr>

      <!-- RESIDENTIAL ADDRESS -->
      <h6 class="text-uppercase text-muted mb-2">Residential Address</h6>

      <div class="row mb-1">
        <div class="col-5 text-muted">Street</div>
        <div class="col-7"><?= $application['residential_address']['street'] ?></div>
      </div>

      <div class="row mb-1">
        <div class="col-5 text-muted">Suburb</div>
        <div class="col-7"><?= $application['residential_address']['suburb'] ?></div>
      </div>

      <div class="row mb-1">
        <div class="col-5 text-muted">Town</div>
        <div class="col-7"><?= $application['residential_address']['town'] ?></div>
      </div>

      <div class="row mb-1">
        <div class="col-5 text-muted">Region</div>
        <div class="col-7"><?= $application['residential_address']['region'] ?></div>
      </div>

      <div class="row mb-1">
        <div class="col-5 text-muted">Email</div>
        <div class="col-7"><?= $application['residential_address']['email'] ?></div>
      </div>

      <div class="row">
        <div class="col-5 text-muted">Mobile</div>
        <div class="col-7"><?= $application['residential_address']['mobile_number'] ?></div>
      </div>

    </div>
  </div>
</div>



            <!-- Column -->
            <!-- Column -->
            <div class="col-lg-8 col-xlg-9 col-md-7">
              <div class="card">
                <!-- Tabs -->
                <ul
                  class="nav nav-pills custom-pills"
                  id="pills-tab"
                  role="tablist"
                >

                  <li class="nav-item">
                    <a
                      class="nav-link"
                      id="pills-timeline-tab"
                      data-bs-toggle="pill"
                      href="#application-details"
                      role="tab"
                      aria-controls="application-details"
                      aria-selected="false"
                      >Application Details</a
                    >
                  </li>
                  <li class="nav-item">
                    <a
                      class="nav-link active"
                      id="pills-profile-tab"
                      data-bs-toggle="pill"
                      href="#properties-info"
                      role="tab"
                      aria-controls="properties-info"
                      aria-selected="true"
                      >Properties</a
                    >
                  </li>
                  <li class="nav-item">
                    <a
                      class="nav-link"
                      id="pills-documents-tab"
                      data-bs-toggle="pill"
                      href="#documents-info"
                      role="tab"
                      aria-controls="documents-info"
                      aria-selected="false"
                      >Documents</a
                    >
                  </li>
                  <li class="nav-item">
                    <a
                      class="nav-link"
                      id="pills-declarations-tab"
                      data-bs-toggle="pill"
                      href="#declarations-info"
                      role="tab"
                      aria-controls="declarations-info"
                      aria-selected="false"
                      >Declarations</a
                    >
                  </li>
                </ul>
                <!-- Tabs -->
                <div class="tab-content" id="pills-tabContent">
                  <!-- Application Details Tab -->
                  <div
                    class="tab-pane fade"
                    id="application-details"
                    role="tabpanel"
                    aria-labelledby="pills-timeline-tab"
                  >
                    <div class="card-body">
                      <h4 class="card-title mb-4">Application Information</h4>
                      <div class="row mb-4">
                        <div class="col-md-6">
                          <div class="card border-primary mb-3">
                            <div class="card-header bg-transparent border-primary">
                              <h5 class="card-title mb-0">Application Status</h5>
                            </div>
                            <div class="card-body">
                              <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-<?= $application['status'] === 'approved' ? 'success' : ($application['status'] === 'rejected' ? 'danger' : 'warning') ?> fs-5 px-3 py-2 me-3">
                                  <?= ucfirst($application['status']) ?>
                                </span>
                              </div>
                              <p class="card-text">
                                <strong>Application Number:</strong> <?= $application['application_number'] ?>
                              </p>
                              <p class="card-text">
                                <strong>Application ID:</strong> <?= $application['application_id'] ?>
                              </p>
                              <p class="card-text">
                                <strong>Loaded By:</strong> <?= $application['personal_details']['loaded_by'] ?>
                              </p>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="card border-info mb-3">
                            <div class="card-header bg-transparent border-info">
                              <h5 class="card-title mb-0">Important Dates</h5>
                            </div>
                            <div class="card-body">
                              <p class="card-text">
                                <strong>Created:</strong> <?= date('M d, Y H:i', strtotime($application['application_created_at'])) ?>
                              </p>
                              <p class="card-text">
                                <strong>Last Updated:</strong> <?= date('M d, Y H:i', strtotime($application['application_updated_at'])) ?>
                              </p>
                              <p class="card-text">
                                <strong>Submission Date:</strong> <?= !empty($application['submission_date']) ? date('M d, Y', strtotime($application['submission_date'])) : 'Not submitted' ?>
                              </p>
                              <?php if (!empty($application['review_date'])): ?>
                              <p class="card-text">
                                <strong>Review Date:</strong> <?= date('M d, Y', strtotime($application['review_date'])) ?>
                              </p>
                              <?php endif; ?>
                              <?php if (!empty($application['approved_date'])): ?>
                              <p class="card-text">
                                <strong>Approved Date:</strong> <?= date('M d, Y', strtotime($application['approved_date'])) ?>
                              </p>
                              <?php endif; ?>
                              <?php if ($application['status'] === 'rejected' && !empty($application['rejection_reason'])): ?>
                              <div class="alert alert-danger mt-3">
                                <strong>Rejection Reason:</strong> <?= htmlspecialchars($application['rejection_reason']) ?>
                              </div>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Sale Type Information -->
                      <div class="row">
                        <div class="col-12">
                          <div class="card border-success mb-3">
                            <div class="card-header bg-transparent border-success">
                              <h5 class="card-title mb-0">Sale Type Information</h5>
                            </div>
                            <div class="card-body">
                              <div class="row">
                                <div class="col-md-4">
                                  <p class="card-text">
                                    <strong>Sale Type:</strong> <?= $application['sale_type']['sale_type'] ?>
                                  </p>
                                </div>
                                <?php if (!empty($application['sale_type']['developer_name'])): ?>
                                <div class="col-md-4">
                                  <p class="card-text">
                                    <strong>Developer Name:</strong> <?= $application['sale_type']['developer_name'] ?>
                                  </p>
                                </div>
                                <?php endif; ?>
                                <div class="col-md-4">
                                  <p class="card-text">
                                    <strong>Property Type:</strong> <?= $application['sale_type']['property_type'] ?>
                                  </p>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Properties Tab -->
                  <div
                    class="tab-pane fade show active"
                    id="properties-info"
                    role="tabpanel"
                    aria-labelledby="pills-profile-tab"
                  >
                    <div class="card-body">
                      <?php if (!empty($application['developments'])): ?>
                      <h4 class="card-title mb-3">Development Breakdown</h4>
                      <?php foreach ($application['developments'] as $devIndex => $development): ?>
                        <div class="card mb-3">
                          <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0">Development <?= $devIndex + 1 ?>: <?= htmlspecialchars($development['development_name']) ?></h6>
                          </div>
                          <div class="card-body">
                            <p class="text-muted mb-3">
                              <?= htmlspecialchars($development['region']) ?>, <?= htmlspecialchars($development['town']) ?><?php if (!empty($development['suburb'])): ?>, <?= htmlspecialchars($development['suburb']) ?><?php endif; ?><?php if (!empty($development['location'])): ?>, <?= htmlspecialchars($development['location']) ?><?php endif; ?>
                            </p>
                            <div class="table-responsive">
                              <table class="table table-sm table-bordered">
                                <thead>
                                  <tr>
                                    <th>House Type</th>
                                    <th>Property Zooning Status</th>
                                    <th>Units</th>
                                    <th>House Size</th>
                                    <th>Property Type</th>
                                    <th>Land Size</th>
                                    <th>Selling Price</th>
                                    <th>Rooms</th>
                                    <th>Bathrooms</th>
                                    <th>Additional Features</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php foreach ($development['house_types'] as $htIndex => $houseType): ?>
                                  <tr>
                                    <td>House Type <?= $htIndex + 1 ?></td>
                                    <td><?= htmlspecialchars($houseType['property_type']) ?></td>
                                    <td><?= (int)$houseType['number_of_units'] ?> total (<?= (int)$houseType['units_remaining'] ?> remaining)</td>
                                    <td><?= $houseType['house_size'] !== null ? htmlspecialchars($houseType['house_size']) . ' m&sup2;' : '-' ?></td>
                                    <td><?= htmlspecialchars($houseType['land_type']) ?></td>
                                    <td><?= htmlspecialchars($houseType['land_size']) ?> m&sup2;</td>
                                    <td>N$ <?= number_format((float)$houseType['selling_price'], 2) ?></td>
                                    <td><?= htmlspecialchars($houseType['number_of_rooms'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($houseType['number_of_bathrooms'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($houseType['additional_features'] ?? '') ?></td>
                                  </tr>
                                  <?php endforeach; ?>
                                </tbody>
                              </table>
                            </div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                      <?php endif; ?>

                      <h4 class="card-title mb-4">Properties</h4>

                      <?php if (!empty($application['properties'])): ?>
                        <?php foreach ($application['properties'] as $index => $property): ?>
                        <div class="card mb-4">
                          <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Property #<?= $index + 1 ?>: <?= htmlspecialchars($property['property_street_name']) ?></h5>
                          </div>
                          <div class="card-body">
                            <div class="row mb-4">
                              <div class="col-md-3">
                                <p class="mb-1"><strong>Property Type</strong></p>
                                <p class="text-muted"><?= htmlspecialchars($property['property_detail_type']) ?></p>
                              </div>
                              <div class="col-md-3">
                                <p class="mb-1"><strong>Land Type</strong></p>
                                <p class="text-muted"><?= htmlspecialchars($property['land_type']) ?></p>
                              </div>
                              <div class="col-md-3">
                                <p class="mb-1"><strong>Land Size</strong></p>
                                <p class="text-muted"><?= htmlspecialchars($property['land_size']) ?></p>
                              </div>
                              <div class="col-md-3">
                                <p class="mb-1"><strong>Selling Price</strong></p>
                                <p class="text-muted"><?= number_format($property['selling_price'], 2) ?></p>
                              </div>
                            </div>

                            <!-- Property Images -->
                            <?php if (!empty($property['images'])): ?>
                            <div class="row mb-4">
                              <div class="col-12">
                                <h6 class="mb-3">Property Images</h6>
                                <div class="row">
                                  <?php foreach ($property['images'] as $img): ?>
                                  <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                    <a href="<?= $baseUrl ?>/html/material/view_document.php?file=<?= urlencode($img['file_path']) ?>" data-lightbox="property-<?= $property['property_id'] ?>">
                                      <img
                                        src="<?= $baseUrl ?>/html/material/view_document.php?file=<?= urlencode($img['thumbnail_path'] ?: $img['file_path']) ?>"
                                        class="img-fluid rounded"
                                        alt="Property Image"
                                        style="height: 150px; width: 100%; object-fit: cover;"
                                      />
                                    </a>
                                    <?php if ($img['is_primary']): ?>
                                    <span class="badge bg-primary mt-1">Primary</span>
                                    <?php endif; ?>
                                  </div>
                                  <?php endforeach; ?>
                                </div>
                              </div>
                            </div>
                            <?php endif; ?>

                            <!-- Property Videos -->
                            <?php if (!empty($property['videos'])): ?>
                            <div class="row">
                              <div class="col-12">
                                <h6 class="mb-3">Property Videos</h6>
                                <div class="row">
                                  <?php foreach ($property['videos'] as $vid): ?>
                                  <div class="col-lg-4 col-md-6 mb-3">
                                    <div class="card">
                                      <video controls class="w-100" style="height: 200px;">
                                        <source src="<?= $baseUrl ?>/html/material/view_document.php?file=<?= urlencode($vid['file_path']) ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                      </video>
                                      <div class="card-footer text-muted">
                                        Duration: <?= htmlspecialchars($vid['video_duration'] ?? '') ?>
                                      </div>
                                    </div>
                                  </div>
                                  <?php endforeach; ?>
                                </div>
                              </div>
                            </div>
                            <?php endif; ?>
                          </div>
                        </div>
                        <?php endforeach; ?>
                      <?php else: ?>
                      <div class="alert alert-info">
                        No properties found for this application.
                      </div>
                      <?php endif; ?>
                    </div>
                  </div>

                  <!-- Documents Tab -->
                  <div
                    class="tab-pane fade"
                    id="documents-info"
                    role="tabpanel"
                    aria-labelledby="pills-documents-tab"
                  >
                    <div class="card-body">
                      <h4 class="card-title mb-4">Documents</h4>

                      <!-- Required Documents -->
                      <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                          <h5 class="card-title mb-0">Required Documents</h5>
                        </div>
                        <div class="card-body">
                          <?php if (!empty($application['documents'])): ?>
                          <div class="table-responsive">
                            <table class="table table-hover">
                              <thead>
                                <tr>
                                  <th>Document Type</th>
                                  <th>Filename</th>
                                  <th>Status</th>
                                  <th>Action</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($application['documents'] as $doc): ?>
                                <tr>
                                  <td><?= htmlspecialchars($doc['document_type']) ?></td>
                                  <td><?= htmlspecialchars($doc['original_filename']) ?></td>
                                  <td>
                                    <span class="badge bg-<?= $doc['is_verified'] ? 'success' : 'warning' ?>">
                                      <?= $doc['is_verified'] ? 'Verified' : 'Pending' ?>
                                    </span>
                                  </td>
                                  <td>
                                    <a href="<?= $baseUrl ?>/html/material/view_document.php?file=<?= urlencode($doc['file_path']) ?>" class="btn btn-sm btn-primary" target="_blank">
										<i class="mdi mdi-eye"></i> View
									</a>

                                  </td>
                                </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </div>
                          <?php else: ?>
                          <div class="alert alert-info">
                            No required documents uploaded yet.
                          </div>
                          <?php endif; ?>
                        </div>
                      </div>

                      <!-- Additional Documents -->
                      <div class="card">
                        <div class="card-header bg-info text-white">
                          <h5 class="card-title mb-0">Additional Documents</h5>
                        </div>
                        <div class="card-body">
                          <?php if (!empty($application['additional_documents'])): ?>
                          <div class="table-responsive">
                            <table class="table table-hover">
                              <thead>
                                <tr>
                                  <th>Document Name</th>
                                  <th>Filename</th>
                                  <th>Action</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($application['additional_documents'] as $doc): ?>
                                <tr>
                                  <td><?= htmlspecialchars($doc['document_name']) ?></td>
                                  <td><?= htmlspecialchars($doc['original_filename']) ?></td>
                                  <td>
                                    <a href="<?= $baseUrl ?>/html/material/view_document.php?file=<?= urlencode($doc['file_path']) ?>" class="btn btn-sm btn-info" target="_blank">
                                      <i class="mdi mdi-eye"></i> View
                                    </a>
                                  </td>
                                </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </div>
                          <?php else: ?>
                          <div class="alert alert-info">
                            No additional documents uploaded yet.
                          </div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Declarations Tab -->
                  <div
                    class="tab-pane fade"
                    id="declarations-info"
                    role="tabpanel"
                    aria-labelledby="pills-declarations-tab"
                  >
                    <div class="card-body">
                      <h4 class="card-title mb-4">Declarations & Signatures</h4>

                      <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                          <h5 class="card-title mb-0">Declaration Details</h5>
                        </div>
                        <div class="card-body">
                          <div class="row">
                            <div class="col-md-6 mb-3">
                              <div class="form-check">
                                <input
                                  type="checkbox"
                                  class="form-check-input"
                                  id="certification_declaration"
                                  <?= $application['declarations']['certification_declaration'] ? 'checked' : '' ?>
                                  disabled
                                >
                                <label class="form-check-label" for="certification_declaration">
                                  <strong>Certification Declaration</strong>
                                </label>
                              </div>
                              <small class="text-muted">I certify that all information provided is true and accurate.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                              <div class="form-check">
                                <input
                                  type="checkbox"
                                  class="form-check-input"
                                  id="authorization_declaration"
                                  <?= $application['declarations']['authorization_declaration'] ? 'checked' : '' ?>
                                  disabled
                                >
                                <label class="form-check-label" for="authorization_declaration">
                                  <strong>Authorization Declaration</strong>
                                </label>
                              </div>
                              <small class="text-muted">I authorize the processing of my application.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                              <div class="form-check">
                                <input
                                  type="checkbox"
                                  class="form-check-input"
                                  id="indemnification_declaration"
                                  <?= $application['declarations']['indemnification_declaration'] ? 'checked' : '' ?>
                                  disabled
                                >
                                <label class="form-check-label" for="indemnification_declaration">
                                  <strong>Indemnification Declaration</strong>
                                </label>
                              </div>
                              <small class="text-muted">I agree to indemnify against any claims.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                              <div class="form-check">
                                <input
                                  type="checkbox"
                                  class="form-check-input"
                                  id="commission_fees_declaration"
                                  <?= $application['declarations']['commission_fees_declaration'] ? 'checked' : '' ?>
                                  disabled
                                >
                                <label class="form-check-label" for="commission_fees_declaration">
                                  <strong>Commission Fees Declaration</strong>
                                </label>
                              </div>
                              <small class="text-muted">I understand and accept the commission fees structure.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                              <div class="form-check">
                                <input
                                  type="checkbox"
                                  class="form-check-input"
                                  id="property_rights_declaration"
                                  <?= $application['declarations']['property_rights_declaration'] ? 'checked' : '' ?>
                                  disabled
                                >
                                <label class="form-check-label" for="property_rights_declaration">
                                  <strong>Property Rights Declaration</strong>
                                </label>
                              </div>
                              <small class="text-muted">I confirm that I have the legal right to sell the property.</small>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Signature Information -->
                      <div class="card">
                        <div class="card-header bg-secondary text-white">
                          <h5 class="card-title mb-0">Signature Information</h5>
                        </div>
                        <div class="card-body">
                          <div class="row">
                            <div class="col-md-6 mb-3">
                              <p class="card-text">
                                <strong>Signature Type:</strong> <?= htmlspecialchars($application['declarations']['signature_type']) ?>
                              </p>
                            </div>
                            <div class="col-md-6 mb-3">
                              <p class="card-text">
                                <strong>Signature Location:</strong> <?= htmlspecialchars($application['declarations']['signature_location']) ?>
                              </p>
                            </div>
                            <div class="col-md-6 mb-3">
                              <p class="card-text">
                                <strong>Signature Date:</strong> <?= !empty($application['declarations']['signature_date']) ? date('M d, Y', strtotime($application['declarations']['signature_date'])) : 'Not signed' ?>
                              </p>
                            </div>
                            <div class="col-md-6 mb-3">
                              <p class="card-text">
                                <strong>OTP Verified At:</strong> <?= !empty($application['declarations']['otp_verified_at']) ? date('M d, Y H:i', strtotime($application['declarations']['otp_verified_at'])) : 'Not verified' ?>
                              </p>
                            </div>
                            <?php if (!empty($application['declarations']['signature_file_path'])): ?>
                            <div class="col-12 mt-3">
                              <p class="card-text"><strong>Signature:</strong></p>
                              <img
                                src="<?= $baseUrl ?>/html/material/view_document.php?file=<?= urlencode($application['declarations']['signature_file_path']) ?>"
                                alt="Applicant Signature"
                                class="img-thumbnail"
                                style="max-width: 300px;"
                              />
                            </div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Legacy tabs kept for compatibility but hidden/empty -->
                  <div
                    class="tab-pane fade"
                    id="current-month"
                    role="tabpanel"
                    aria-labelledby="pills-timeline-tab"
                  >
                    <div class="card-body">
                      <!-- This tab is now replaced by Application Details tab -->
                    </div>
                  </div>
                  <div
                    class="tab-pane fade"
                    id="last-month"
                    role="tabpanel"
                    aria-labelledby="pills-profile-tab"
                  >
                    <div class="card-body">
                      <!-- This tab is now replaced by Properties tab -->
                    </div>
                  </div>
                  <div
                    class="tab-pane fade"
                    id="previous-month"
                    role="tabpanel"
                    aria-labelledby="pills-setting-tab"
                  >
                    <div class="card-body">
                      <!-- This tab is now replaced by Documents/Declarations tabs -->
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Column -->
          </div>
          <!-- Row -->
          <!-- -------------------------------------------------------------- -->
          <!-- End PAge Content -->
          <!-- -------------------------------------------------------------- -->
        </div>
        <!-- -------------------------------------------------------------- -->
        <!-- End Container fluid  -->
        <!-- -------------------------------------------------------------- -->
        <!-- -------------------------------------------------------------- -->
        <!-- footer -->
        <!-- -------------------------------------------------------------- -->
        <footer class="footer text-center">
          All Rights Reserved by NURU.
        </footer>
        <!-- -------------------------------------------------------------- -->
        <!-- End footer -->
        <!-- -------------------------------------------------------------- -->
      </div>
      <!-- -------------------------------------------------------------- -->
      <!-- End Page wrapper  -->
      <!-- -------------------------------------------------------------- -->
    </div>
    <!-- -------------------------------------------------------------- -->
    <!-- End Wrapper -->
    <!-- -------------------------------------------------------------- -->
    <!-- -------------------------------------------------------------- -->
    <!-- customizer Panel -->
    <!-- -------------------------------------------------------------- -->

    <div class="chat-windows"></div>
    <!-- -------------------------------------------------------------- -->
    <!-- All Jquery -->
    <!-- -------------------------------------------------------------- -->
    <script src="<?= $baseUrl ?>/assets/libs/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="<?= $baseUrl ?>/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- apps -->
    <script src="<?= $baseUrl ?>/dist/js/app.min.js"></script>
    <script src="<?= $baseUrl ?>/dist/js/app.init.js"></script>
    <script src="<?= $baseUrl ?>/dist/js/app-style-switcher.js"></script>
    <!-- slimscrollbar scrollbar JavaScript -->
    <script src="<?= $baseUrl ?>/assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="<?= $baseUrl ?>/assets/extra-libs/sparkline/sparkline.js"></script>
    <!--Wave Effects -->
    <script src="<?= $baseUrl ?>/dist/js/waves.js"></script>
    <!--Menu sidebar -->
    <script src="<?= $baseUrl ?>/dist/js/sidebarmenu.js?v=20260720"></script>
    <!--Custom JavaScript -->
    <script src="<?= $baseUrl ?>/dist/js/feather.min.js"></script>
    <script src="<?= $baseUrl ?>/dist/js/custom.min.js"></script>
  </body>
</html>
