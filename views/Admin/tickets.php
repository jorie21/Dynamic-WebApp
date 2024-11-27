<?php
// Make sure tickets are passed to the view
$tickets = $tickets ?? []; // Ensure tickets is always an array (in case it's null)

// Continue with pagination logic
$rowsPerPage = 7;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$startIndex = ($page - 1) * $rowsPerPage;
$totalTickets = count($tickets);
$totalPages = ceil($totalTickets / $rowsPerPage);
$offset = ($page - 1) * $rowsPerPage;
$paginatedTickets = array_slice($tickets, $offset, $rowsPerPage); // Fixed the variable name to match


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/public/styles/AdminCss/Ticket.css">
    <title>Tickets</title>
</head>
<body>
<?php require($_SERVER['DOCUMENT_ROOT'] . '/components/sidebar.php') ?>

<div class="container">
    <div class="header" style=" box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;">
        Colegio de Montalban - <span style="color: white;">Tickets</span>
    </div>

    <div class="ticket-table">
        <div class="search-bar">
                <form method="get" action="">
                    <div class="search-icon-container">
                        <i class="fas fa-search"></i> 
                        <input type="text" id="searchInput" placeholder="Search tickets..." onkeyup="filterTable()" />
                    </div>
                </form>
        </div>

        <table>
            <thead>
                <tr>
    
                    <th>Email</th>
                    <th>Institute</th>
                    <th>Concern</th>
                    <th>Attachment</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($paginatedTickets)): ?>
                    <?php foreach ($paginatedTickets as $ticket): ?>
                        <tr>
                        
                            <td><?php echo htmlspecialchars($ticket['Email']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['institute']); ?></td>
                            <td class="concern">
                                <?php 
                                    $concern = htmlspecialchars($ticket['concern']);
                                    echo strlen($concern) > 50 
                                        ? substr($concern, 0, 50) . '... <a href="#" onclick="viewConcern(\'' . htmlspecialchars($ticket['_id']) . '\')">See More</a>'
                                        : $concern;
                                ?>
                            </td>
                            <td class="file-attachment">
                                <?php if (!empty($ticket['file'])): ?>
                                    <a href="<?php echo htmlspecialchars($ticket['file']); ?>" target="_blank">Download</a>
                                <?php else: ?>
                                    No attachment
                                <?php endif; ?>
                            </td>
                            <td>
                                <select name="status" onchange="updateTicketStatus(this, '<?php echo $ticket['_id']; ?>')"
                                    style="background-color: <?php echo $ticket['status'] === 'Active' ? '#28a745' : '#007bff'; ?>;">
                                    <option value="Active" <?php echo $ticket['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="Done" <?php echo $ticket['status'] === 'Done' ? 'selected' : ''; ?>>Done</option>
                                </select>
                            </td>
                            <td>
                                <button class="view-button" onclick="openViewModal(<?php echo htmlspecialchars(json_encode($ticket)); ?>)">
                                    View
                                </button>
                                <button class="email-button" onclick="openEmailModal('<?php echo htmlspecialchars($ticket['Email']); ?>')">
                                    Send Email
                                </button>
                                <button class="delete-button" onclick="deleteTicket('<?php echo htmlspecialchars($ticket['_id']); ?>')">
                                    Delete
                                </button>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No tickets found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <button <?php if ($page <= 1) echo 'disabled'; ?> 
                onclick="window.location.href='?page=<?php echo $page - 1; ?>'">Previous</button>
            <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
            <button <?php if ($page >= $totalPages) echo 'disabled'; ?> 
                onclick="window.location.href='?page=<?php echo $page + 1; ?>'">Next</button>
        </div>
    </div>
</div>
<!-- View Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content view-modal-content">
        <span class="close" onclick="closeViewModal()">&times;</span>
        <h2 class="modal-title">Ticket Details</h2>
        <div id="ticketDetails">
            <div class="view-detail-group">
                <strong>Email:</strong> <span id="viewEmail"></span>
            </div>
            <div class="view-detail-group">
                <strong>Institute:</strong> <span id="viewInstitute"></span>
            </div>
            <div class="view-detail-group">
                <strong>Concern:</strong> <span id="viewConcern"></span>
            </div>
            <div class="view-detail-group">
                <strong>Status:</strong> <span id="viewStatus"></span>
            </div>
            <div class="view-detail-group">
                <strong>Attachment:</strong> <a id="viewAttachment" href="#" target="_blank">View File</a>
            </div>
        </div>
    </div>
</div>
<!-- Send Email Modal -->
<div id="emailModal" class="modal">
    <div class="modal-content email-modal-content">
        <span class="close" onclick="closeEmailModal()">&times;</span>
        <h2 class="modal-title">Send Email</h2>
        <form id="sendEmailForm" method="post" action="send_email.php">
            <div class="modal-input-group">
                <label for="emailRecipient">Recipient Email:</label>
                <input type="email" id="emailRecipient" name="emailRecipient" placeholder="Enter recipient's email" required>
            </div>
            <div class="modal-input-group">
                <label for="emailSubject">Subject:</label>
                <input type="text" id="emailSubject" name="emailSubject" placeholder="Enter email subject" required>
            </div>
            <div class="modal-input-group">
                <label for="emailMessage">Message:</label>
                <textarea id="emailMessage" name="emailMessage" placeholder="Enter your message" rows="5" required></textarea>
            </div>
            <button class="save-button" type="submit">Send Email</button>
        </form>
    </div>
</div>


<script>

function updateTicketStatus(selectElement, ticketId) {
    const selectedValue = selectElement.value;
    
    // Send the status update request to the server using AJAX
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "TicketController.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onload = function() {
        if (xhr.status === 200) {
            // Optionally update the UI with the new status
            selectElement.style.backgroundColor = selectedValue === 'Active' ? '#28a745' : '#007bff';
        } else {
            alert("Failed to update ticket status.");
        }
    };
    xhr.send("ticketId=" + ticketId + "&status=" + selectedValue);
}
</script>
<script>
function openViewModal(ticket) {
    const modal = document.getElementById('viewModal');
    modal.classList.add('show'); // Add 'show' class to trigger the transition

    // Populate modal content
    document.getElementById('viewEmail').textContent = ticket.Email || 'N/A';
    document.getElementById('viewInstitute').textContent = ticket.institute || 'N/A';
    document.getElementById('viewConcern').textContent = ticket.concern || 'N/A';
    document.getElementById('viewStatus').textContent = ticket.status || 'N/A';
    if (ticket.file) {
        document.getElementById('viewAttachment').href = ticket.file;
        document.getElementById('viewAttachment').textContent = 'Download';
    } else {
        document.getElementById('viewAttachment').textContent = 'No Attachment';
        document.getElementById('viewAttachment').removeAttribute('href');
    }
}

function closeViewModal() {
    const modal = document.getElementById('viewModal');
    modal.classList.remove('show'); // Remove 'show' class to trigger fade-out
}

function openEmailModal(email) {
    const modal = document.getElementById('emailModal');
    modal.classList.add('show'); // Add 'show' class to trigger the transition
    document.getElementById('emailRecipient').value = email;
}

function closeEmailModal() {
    const modal = document.getElementById('emailModal');
    modal.classList.remove('show'); // Remove 'show' class to trigger fade-out
}


function deleteTicket(ticketId) {
    if (confirm('Are you sure you want to delete this ticket?')) {
        alert(`Ticket with ID ${ticketId} deleted.`);
    }
}
function updateTicketStatus(selectElement, ticketId) {
    const selectedValue = selectElement.value;

    const activeColor = '#28a745'; 
    const doneColor = '#007bff'; 

  
    selectElement.style.backgroundColor = selectedValue === 'Active' ? activeColor : doneColor;
}


function filterTable() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase(); 
    const tableRows = document.querySelectorAll('tbody tr'); 
    tableRows.forEach(row => {
        const userName = row.children[1].textContent.toLowerCase(); 
        const institute = row.children[2].textContent.toLowerCase(); 
        const concern = row.children[3].textContent.toLowerCase(); 
        const status = row.children[5].textContent.toLowerCase(); 
        
        // Check if any column matches the search input
        if (
            userName.includes(searchInput) || 
            institute.includes(searchInput) || 
            concern.includes(searchInput) || 
            status.includes(searchInput)
        ) {
            row.style.display = ''; 
        } else {
            row.style.display = 'none'; 
        }
    });
}


</script>


</body>
</html>
