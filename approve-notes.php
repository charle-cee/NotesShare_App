<?php
session_start();
error_reporting(0);
include('config.php');

if (strlen($_SESSION['ocasuid']) == 0) {
    header('location:logout.php');
    exit();
} else {
    $uid = $_SESSION['ocasuid'];

    // Verify admin
    $sql = "SELECT usertype FROM registered_users WHERE id = :uid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':uid', $uid, PDO::PARAM_STR);
    $query->execute();
    $user = $query->fetch(PDO::FETCH_OBJ);

    if (!$user || $user->usertype !== 'admin') {
        echo "<script>window.location.href='dashboard.php';</script>";
        exit();
    }

    // Approve Note
    if (isset($_GET['approveid'])) {
        $noteId = intval($_GET['approveid']);

        $sql = "UPDATE tblnotes SET Status = 'Approved' WHERE ID = :noteId";
        $query = $dbh->prepare($sql);
        $query->bindParam(':noteId', $noteId, PDO::PARAM_INT);

        if ($query->execute()) {
            $sqlUser = "SELECT u.name, u.email, n.NotesTitle, n.Subject, n.File1 
                        FROM tblnotes n 
                        JOIN registered_users u ON u.id = n.UserID 
                        WHERE n.ID = :noteId";
            $queryUser = $dbh->prepare($sqlUser);
            $queryUser->bindParam(':noteId', $noteId, PDO::PARAM_INT);
            $queryUser->execute();
            $userData = $queryUser->fetch(PDO::FETCH_OBJ);

            if ($userData) {
                echo json_encode([
                    'status' => 'Approved',
                    'noteId' => $noteId,
                    'name' => $userData->name,
                    'email' => $userData->email,
                    'title' => $userData->NotesTitle,
                    'subject' => $userData->Subject,
                    'filename' => $userData->File1
                ]);
                exit();
            } else {
                echo json_encode(['error' => 'User or note details not found.']);
                exit();
            }
        } else {
            echo json_encode(['error' => 'Error approving note.']);
            exit();
        }
    }

    // Reject Note
    if (isset($_GET['rejectid'])) {
        $noteId = intval($_GET['rejectid']);

        $sql = "UPDATE tblnotes SET Status = 'Rejected' WHERE ID = :noteId";
        $query = $dbh->prepare($sql);
        $query->bindParam(':noteId', $noteId, PDO::PARAM_INT);

        if ($query->execute()) {
            $sqlUser = "SELECT u.name, u.email, n.NotesTitle, n.Subject, n.File1 
                        FROM tblnotes n 
                        JOIN registered_users u ON u.id = n.UserID 
                        WHERE n.ID = :noteId";
            $queryUser = $dbh->prepare($sqlUser);
            $queryUser->bindParam(':noteId', $noteId, PDO::PARAM_INT);
            $queryUser->execute();
            $userData = $queryUser->fetch(PDO::FETCH_OBJ);

            if ($userData) {
                echo json_encode([
                    'status' => 'Rejected',
                    'noteId' => $noteId,
                    'name' => $userData->name,
                    'email' => $userData->email,
                    'title' => $userData->NotesTitle,
                    'subject' => $userData->Subject,
                    'filename' => $userData->File1
                ]);
                exit();
            } else {
                echo json_encode(['error' => 'User or note details not found.']);
                exit();
            }
        } else {
            echo json_encode(['error' => 'Error rejecting note.']);
            exit();
        }
    }
    if (isset($_GET['previewid'])) {
    $noteId = intval($_GET['previewid']);
    $sql = "SELECT File1 FROM tblnotes WHERE ID = :noteId";
    $query = $dbh->prepare($sql);
    $query->bindParam(':noteId', $noteId, PDO::PARAM_INT);
    $query->execute();
    $note = $query->fetch(PDO::FETCH_OBJ);

    if ($note && file_exists("notes/" . $note->File1)) {
        echo json_encode([
            'status' => 'success',
            'file' => $note->File1
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'File not found.'
        ]);
    }
    exit;
}

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Approve Notes</title>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="includes/logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    
    <style>
        :root {
            --primary-color: #003366;
            --secondary-color: #FFD700;
            --accent-color: #FFFFFF;
        }
        
        body {
            background-color: var(--accent-color);
            color: #333;
        }
        
        .table thead th {
            background-color: var(--primary-color);
            color: var(--accent-color);
        }
        
        .btn-preview {
            background-color: var(--secondary-color);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .btn-preview:hover {
            background-color: #e6c200;
            color: var(--primary-color);
        }
        
        .btn-approve {
            background-color: var(--primary-color);
            color: var(--accent-color);
        }
        
        .btn-approve:hover {
            background-color: #002244;
            color: var(--accent-color);
        }
        
        .btn-reject {
            background-color: #dc3545;
            color: var(--accent-color);
        }
        
        .btn-reject:hover {
            background-color: #c82333;
            color: var(--accent-color);
        }
        
        .note-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #004080 100%);
            color: var(--accent-color);
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-bottom: 4px solid var(--secondary-color);
        }
        
        .preview-modal iframe {
            width: 100%;
            height: 70vh;
            border: none;
            border-radius: 8px;
        }
        
        .modal-header {
            background-color: var(--primary-color);
            color: var(--accent-color);
            border-bottom: 3px solid var(--secondary-color);
        }
        
        .modal-footer .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .modal-footer .btn-primary:hover {
            background-color: #002244;
            border-color: #002244;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color) 0%, #002244 100%);
        }
        
        .sidebar .navbar .navbar-nav .nav-item .nav-link {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .sidebar .navbar .navbar-nav .nav-item .nav-link:hover {
            color: var(--secondary-color);
        }
        
        .sidebar .navbar .dropdown-menu {
            background-color: var(--primary-color);
        }

    #pdfContainer {
        scroll-behavior: smooth;
        padding: 10px;
    }
    .pdf-page {
        background: white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .page-number {
        z-index: 10;
    }
    </style>
</head>
<body>
<div class="container-fluid position-relative bg-white d-flex p-0">
    <?php include_once('includes/sidebar.php'); ?>
    <div class="content">
        <?php include_once('includes/header2.php'); ?>

        <div class="container-fluid pt-4 px-4">
            <div class="note-header text-center">
                <h4 class="fw-bold"><i class="fas fa-file-alt me-2"></i> Approve Pending Notes</h4>
                <p class="mb-0">Review and approve or reject submitted notes</p>
            </div>
            <div class="bg-white rounded shadow p-4" style="border-top: 4px solid var(--secondary-color);">
                <div class="table-responsive table-striped">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Uploader</th>
                                <th>Subject</th>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $sql = "SELECT n.*, u.name FROM tblnotes n 
                                JOIN registered_users u ON u.id = n.UserID 
                                WHERE n.Status = 'Pending' 
                                ORDER BY n.CreationDate DESC, n.ID DESC";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);
                        $cnt = 1;

                        if ($query->rowCount() > 0) {
                            foreach ($results as $row) {
                        ?>
                        <tr>
                            <td><?php echo $cnt++; ?></td>
                            <td><?php echo htmlentities($row->name); ?></td>
                            <td><?php echo htmlentities($row->Subject); ?></td>
                            <td><?php echo htmlentities($row->NotesType); ?></td>
                            <td><?php echo htmlentities($row->NotesTitle); ?></td>
                            <td><?php echo htmlentities($row->CreationDate); ?></td>
                            <td>
                                <button onclick="previewNote(<?php echo $row->ID; ?>)" class="btn btn-sm btn-preview">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <button onclick="approveNote(<?php echo $row->ID; ?>)" class="btn btn-sm btn-approve">
                                    <i class="fas fa-check-circle"></i> Approve
                                </button>
                                <button onclick="rejectNote(<?php echo $row->ID; ?>)" class="btn btn-sm btn-reject">
                                    <i class="fas fa-times-circle"></i> Reject
                                </button>
                            </td>
                        </tr>
                        <?php }} else { ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-check-circle fa-2x text-muted mb-3"></i>
                                <h5 class="text-muted">No pending notes found</h5>
                                <p class="text-muted">All notes have been reviewed</p>
                            </td>
                        </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php include_once('includes/footer.php'); ?>
    </div>
    <?php include_once('includes/back-totop.php'); ?>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel"><i class="fas fa-file-alt me-2"></i> Note Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body preview-modal">
                <div id="googleViewerContainer" style="display:none;">
                    <iframe id="googleViewerFrame" src="" style="width:100%; height:70vh; border:none;"></iframe>
                    <div class="text-center mt-3">
                        <a href="#" id="downloadOriginal" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download me-1"></i> Download Original File
                        </a>
                    </div>
                </div>
                <div id="nativeViewerContainer">
                    <iframe id="nativeViewerFrame" src="" style="width:100%; height:70vh; border:none;"></iframe>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="approveAfterPreview">Approve Note</button>
            </div>
        </div>
    </div>
</div>

<!-- Core Scripts -->
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<!-- PDF.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';
</script>

<script>
let pdfDocument = null;
let allPages = []; // Array to store all page canvases

function previewNote(id) {
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    $('#nativeViewerContainer').html(`<div class="text-center py-5"><div class="spinner-border text-primary"></div><p>Loading preview...</p></div>`);
    modal.show();

    // Cleanup any existing PDF document
    cleanupPDF();

    $.ajax({
        url: 'approve-notes.php',
        type: 'GET',
        data: { previewid: id },
        dataType: 'json',
        success: function(response) {
            if (response.status !== 'success' || !response.file) {
                showError("Invalid file.");
                return;
            }

            const filename = response.file;
            const extension = filename.split('.').pop().toLowerCase();
            const encodedFilename = encodeURIComponent(filename);
            const fileUrl = window.location.origin + '/notes/' + encodedFilename;

            if (extension === 'pdf') {
                $('#nativeViewerContainer').html(`
                    <div id="pdfContainer" style="width: 100%; overflow-y: auto; max-height: 70vh;"></div>
                    <div class="text-center mt-3">
                        <a href="notes/${encodedFilename}" download class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download me-1"></i> Download PDF
                        </a>
                    </div>
                `);
                renderAllPDFPages('notes/' + encodedFilename);
            } else if (['doc', 'docx', 'ppt', 'pptx'].includes(extension)) {
                $('#nativeViewerContainer').html(`
                    <iframe 
                        src="https://view.officeapps.live.com/op/embed.aspx?src=${fileUrl}" 
                        style="width:100%; height:70vh; border:none;"
                        frameborder="0"
                    ></iframe>
                    <div class="text-center mt-3">
                        <a href="notes/${encodedFilename}" download class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download me-1"></i> Download File
                        </a>
                    </div>
                `);
            } else {
                $('#nativeViewerContainer').html(`
                    <div class="alert alert-info text-center py-5">
                        <i class="fas fa-file-alt fa-3x mb-3"></i>
                        <h4>Preview Not Supported</h4>
                        <p>This file format cannot be previewed in the browser.</p>
                        <a href="notes/${encodedFilename}" download class="btn btn-primary">
                            <i class="fas fa-download me-1"></i> Download File
                        </a>
                    </div>
                `);
            }
        },
        error: function(xhr) {
            showError("Failed to load preview.");
        }
    });
}

function renderAllPDFPages(pdfPath) {
    const pdfContainer = document.getElementById('pdfContainer');
    pdfContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div><p>Loading PDF pages...</p></div>';
    
    cleanupPDF();

    pdfjsLib.getDocument(pdfPath).promise
        .then(pdf => {
            pdfDocument = pdf;
            totalPages = pdf.numPages;
            allPages = [];
            
            // Create a container for all pages
            pdfContainer.innerHTML = '';
            
            // Render all pages sequentially
            const renderPromises = [];
            for (let i = 1; i <= totalPages; i++) {
                renderPromises.push(renderPDFPage(pdf, i, pdfContainer));
            }
            
            return Promise.all(renderPromises);
        })
        .catch(err => {
            console.error("PDF.js error:", err);
            showError("Could not load PDF document.");
        });
}

function renderPDFPage(pdf, pageNumber, container) {
    return pdf.getPage(pageNumber).then(page => {
        // Create a container for this page
        const pageDiv = document.createElement('div');
        pageDiv.className = 'pdf-page mb-3';
        pageDiv.style.position = 'relative';
        
        // Add page number indicator
        const pageNumberDiv = document.createElement('div');
        pageNumberDiv.className = 'page-number badge bg-secondary';
        pageNumberDiv.textContent = `Page ${pageNumber}`;
        pageNumberDiv.style.position = 'absolute';
        pageNumberDiv.style.top = '5px';
        pageNumberDiv.style.left = '5px';
        pageDiv.appendChild(pageNumberDiv);
        
        // Create canvas for this page
        const canvas = document.createElement('canvas');
        canvas.className = 'pdf-page-canvas';
        canvas.style.border = '1px solid #eee';
        canvas.style.margin = '0 auto';
        canvas.style.display = 'block';
        
        // Calculate scale to fit container width
        const containerWidth = container.offsetWidth - 40; // Account for padding
        const viewport = page.getViewport({ scale: 1.0 });
        const scale = containerWidth / viewport.width;
        const scaledViewport = page.getViewport({ scale });
        
        // Set canvas dimensions
        canvas.width = scaledViewport.width;
        canvas.height = scaledViewport.height;
        
        // Add canvas to page container
        pageDiv.appendChild(canvas);
        
        // Add page container to main container
        container.appendChild(pageDiv);
        
        // Render the page
        return page.render({
            canvasContext: canvas.getContext('2d'),
            viewport: scaledViewport
        }).promise;
    });
}

function cleanupPDF() {
    if (pdfDocument) {
        pdfDocument.destroy();
        pdfDocument = null;
    }
    allPages = [];
}
document.getElementById('approveAfterPreview').addEventListener('click', function() {
    if (currentPreviewNoteId) {
        approveNote(currentPreviewNoteId);
        const previewModal = bootstrap.Modal.getInstance(document.getElementById('previewModal'));
        previewModal.hide();
    }
});

function showError(message) {
    $('#nativeViewerContainer').html(`
        <div class="alert alert-danger text-center py-4">
            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
            <h5>Error Loading Preview</h5>
            <p>${message}</p>
            <button onclick="location.reload()" class="btn btn-sm btn-primary mt-2">
                <i class="fas fa-sync-alt me-1"></i> Try Again
            </button>
        </div>
    `);
}

// Clean up when modal is closed
$('#previewModal').on('hidden.bs.modal', function () {
    cleanupPDF();
    $('#nativeViewerContainer').empty();
});
</script>

<script>
// Setup the approve button in the preview modal
document.getElementById('approveAfterPreview').addEventListener('click', function() {
    if (currentPreviewNoteId) {
        approveNote(currentPreviewNoteId);
        const previewModal = bootstrap.Modal.getInstance(document.getElementById('previewModal'));
        previewModal.hide();
    }
});

function approveNote(id) {
    Swal.fire({
        title: 'Approve this note?',
        text: 'Are you sure you want to approve this note?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#003366',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, approve it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                type: 'GET',
                url: 'approve-notes.php',
                data: { approveid: id },
                dataType: 'json',
                success: function(response) {
                    if (response && response.status === 'Approved') {
                        // Send approval email
                        $.ajax({
                            type: 'POST',
                            url: 'send-note-email.php',
                            data: {
                                action: 'approve',
                                noteid: id,
                                email: response.email,
                                name: response.name,
                                title: response.title,
                                subject: response.subject,
                                filename: response.filename
                            },
                            success: function() {
                                Swal.fire({
                                    title: 'Approved!',
                                    text: 'The note has been approved and notification sent.',
                                    icon: 'success',
                                    confirmButtonColor: '#003366'
                                }).then(() => {
                                    location.reload();
                                });
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'Approved!',
                                    text: 'The note was approved but email notification failed.',
                                    icon: 'warning',
                                    confirmButtonColor: '#003366'
                                }).then(() => {
                                    location.reload();
                                });
                            }
                        });
                    } else if (response && response.error) {
                        Swal.fire('Error', response.error, 'error');
                    } else {
                        Swal.fire('Error', 'Unexpected server response.', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to approve the note.', 'error');
                }
            });
        }
    });
}

function rejectNote(id) {
    Swal.fire({
        title: 'Reject this note?',
        text: 'Are you sure you want to reject this note?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, reject it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                type: 'GET',
                url: 'approve-notes.php',
                data: { rejectid: id },
                dataType: 'json',
                success: function(response) {
                    if (response && response.status === 'Rejected') {
                        // Send rejection email
                        $.ajax({
                            type: 'POST',
                            url: 'send-note-email.php',
                            data: {
                                action: 'reject',
                                noteid: id,
                                email: response.email,
                                name: response.name,
                                title: response.title,
                                subject: response.subject,
                                filename: response.filename
                            },
                            success: function() {
                                Swal.fire({
                                    title: 'Rejected!',
                                    text: 'The note has been rejected and notification sent.',
                                    icon: 'success',
                                    confirmButtonColor: '#dc3545'
                                }).then(() => {
                                    location.reload();
                                });
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'Rejected!',
                                    text: 'The note was rejected but email notification failed.',
                                    icon: 'warning',
                                    confirmButtonColor: '#dc3545'
                                }).then(() => {
                                    location.reload();
                                });
                            }
                        });
                    } else if (response && response.error) {
                        Swal.fire('Error', response.error, 'error');
                    } else {
                        Swal.fire('Error', 'Unexpected server response.', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to reject the note.', 'error');
                }
            });
        }
    });
}
</script>
</body>
</html>