
/**
 * MPR Officers - Monthly Performance Report Officers View JavaScript
 */

$(document).ready(function() {
    // Default to current month/year
    const currentDate = new Date();
    let selectedMonth = $('#smonth').val() || currentDate.toLocaleString('default', { month: 'long' });
    let selectedYear = $('#syear').val() || currentDate.getFullYear().toString();
    
    // Initialize with current month data
    loadOfficerData(selectedMonth, selectedYear);
    
    // Form submission handler
    $('#mprOfficerForm').on('submit', function(e) {
        e.preventDefault();
        selectedMonth = $('#smonth').val();
        selectedYear = $('#syear').val();
        loadOfficerData(selectedMonth, selectedYear);
    });
    
    // Function to load officer data from API
    function loadOfficerData(month, year) {
        // Show loading state
        $('#officerTableContainer').html('<div class="loading"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        
        // Call the API to get data
        $.ajax({
            url: 'api/get_mpr_officers.php',
            type: 'GET',
            data: {
                month: month,
                year: year
            },
            dataType: 'json',
            success: function(response) {
                // Update period display
                const today = new Date().toISOString().split('T')[0];
                $('#officerPeriodDisplay').html(`
                    <i class="fas fa-users me-2"></i>
                    ${response.period.month} ${response.period.year} Officer Performance Summary
                `);
                
                // Render the officers table
                renderOfficersTable(response);
            },
            error: function(xhr, status, error) {
                // Handle error
                $('#officerTableContainer').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Error loading officer data: ${error}
                    </div>
                `);
                console.error('Error fetching officer data:', error);
            }
        });
    }
    
    // Function to render officers table
    function renderOfficersTable(data) {
        // Get all income lines
        const incomeLines = data.incomeLines;
        
        // Start building the table HTML
        let tableHtml = `
            <table id="officerTable" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Officer Name</th>
        `;
        
        // Add income line columns
        incomeLines.forEach(line => {
            tableHtml += `<th class="text-right">${line}</th>`;
        });
        
        // Add total column
        tableHtml += `
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        // Add rows for each officer
        data.officers.forEach(officer => {
            tableHtml += `
                <tr>
                    <td>${officer.officerName}</td>
            `;
            
            // Add cells for each income line
            incomeLines.forEach(line => {
                let amount = 0;
                
                // Find the collection for this income line
                officer.collections.forEach(collection => {
                    if (collection.incomeLine === line) {
                        amount = collection.amount;
                    }
                });
                
                tableHtml += `
                    <td class="text-right">
                        ${formatNumber(amount)}
                    </td>
                `;
            });
            
            // Add total
            tableHtml += `
                    <td class="text-right font-weight-bold">
                        ${formatNumber(officer.totalCollected)}
                    </td>
                </tr>
            `;
        });
        
        // Add footer with totals
        tableHtml += `
                </tbody>
                <tfoot>
                    <tr class="bg-light">
                        <th>Total</th>
        `;
        
        // Add income line totals
        incomeLines.forEach(line => {
            const total = data.incomeTotals[line] || 0;
            tableHtml += `
                <th class="text-right">
                    ${formatNumber(total)}
                </th>
            `;
        });
        
        // Add grand total
        tableHtml += `
                        <th class="text-right">${formatNumber(data.grandTotal)}</th>
                    </tr>
                </tfoot>
            </table>
        `;
        
        // Update the container with the table
        $('#officerTableContainer').html(tableHtml);
        
        // Initialize DataTable
        const table = $('#officerTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'copyHtml5',
                    footer: true,
                    text: 'Copy Data',
                    className: 'hidden-button'
                },
                {
                    extend: 'excelHtml5',
                    footer: true,
                    text: 'Export to Excel',
                    title: `${data.period.month} ${data.period.year} Officer Performance Summary`,
                    className: 'hidden-button'
                }
            ],
            paging: false,
            searching: true,
            ordering: true,
            info: true
        });
        
        // Connect custom buttons to DataTables buttons
        $('#copyButton').off('click').on('click', function() {
            $('.buttons-copy').click();
        });
        
        $('#excelButton').off('click').on('click', function() {
            $('.buttons-excel').click();
        });
        
        // Use custom search box
        $('#tableSearch').off('keyup').keyup(function() {
            table.search($(this).val()).draw();
        });
    }
    
    // Format number with commas
    function formatNumber(number) {
        return new Intl.NumberFormat().format(number);
    }
});
