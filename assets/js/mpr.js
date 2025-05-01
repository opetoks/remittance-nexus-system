
/**
 * MPR - Monthly Performance Report JavaScript
 */

$(document).ready(function() {
    // Default to current month/year
    const currentDate = new Date();
    let selectedMonth = $('#smonth').val() || currentDate.toLocaleString('default', { month: 'long' });
    let selectedYear = $('#syear').val() || currentDate.getFullYear().toString();
    
    // Initialize with current month data
    loadMPRData(selectedMonth, selectedYear);
    
    // Form submission handler
    $('#mprPeriodForm').on('submit', function(e) {
        e.preventDefault();
        selectedMonth = $('#smonth').val();
        selectedYear = $('#syear').val();
        loadMPRData(selectedMonth, selectedYear);
    });
    
    // Function to load MPR data from API
    function loadMPRData(month, year) {
        // Show loading state
        $('#mprTableContainer').html('<div class="loading"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        
        // Call the API to get data
        $.ajax({
            url: 'api/get_mpr_summary.php',
            type: 'GET',
            data: {
                month: month,
                year: year
            },
            dataType: 'json',
            success: function(response) {
                // Update period display
                const today = new Date().toISOString().split('T')[0];
                $('#periodDisplay').html(`
                    <i class="fas fa-file-alt me-2"></i>
                    ${response.period.month} ${response.period.year} Collection Summary as at ${today}
                `);
                
                // Render the MPR table
                renderMPRTable(response);
            },
            error: function(xhr, status, error) {
                // Handle error
                $('#mprTableContainer').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Error loading MPR data: ${error}
                    </div>
                `);
                console.error('Error fetching MPR data:', error);
            }
        });
    }
    
    // Function to render MPR table
    function renderMPRTable(data) {
        // Get the maximum number of days in the month
        const daysInMonth = new Date(data.period.year, new Date(Date.parse(data.period.month + " 1")).getMonth() + 1, 0).getDate();
        
        // Start building the table HTML
        let tableHtml = `
            <table id="mprTable" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Income Line</th>
        `;
        
        // Add day columns
        for (let day = 1; day <= daysInMonth; day++) {
            // Check if this day is a Sunday (based on the sundays array)
            const isSunday = data.sundays.includes(day);
            const headerClass = isSunday ? 'sunday-header' : '';
            const dayLabel = isSunday ? 'Sun' : 'Day';
            const dayFormatted = day < 10 ? '0' + day : day;
            
            tableHtml += `
                <th class="text-right ${headerClass}">
                    <span class="day-label">${dayLabel}</span>
                    ${dayFormatted}
                </th>
            `;
        }
        
        // Add total column
        tableHtml += `
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        // Add rows for each income line
        data.data.forEach(line => {
            tableHtml += `
                <tr>
                    <td>${line.incomeLine}</td>
            `;
            
            // Add cells for each day
            for (let day = 1; day <= daysInMonth; day++) {
                // Check if this day is a Sunday
                const isSunday = data.sundays.includes(day);
                const cellClass = isSunday ? 'sunday-cell' : '';
                const amount = line.days[day] || 0;
                
                tableHtml += `
                    <td class="text-right ${cellClass}">
                        ${formatNumber(amount)}
                    </td>
                `;
            }
            
            // Add total
            tableHtml += `
                    <td class="text-right font-weight-bold">
                        ${formatNumber(line.total)}
                    </td>
                </tr>
            `;
        });
        
        // Add footer with totals
        tableHtml += `
                </tbody>
                <tfoot>
                    <tr class="totals-row">
                        <th>Daily Totals</th>
        `;
        
        // Add day totals
        for (let day = 1; day <= daysInMonth; day++) {
            // Check if this day is a Sunday
            const isSunday = data.sundays.includes(day);
            const cellClass = isSunday ? 'bg-danger text-white' : '';
            const dayTotal = data.totals[day] || 0;
            
            tableHtml += `
                <th class="text-right ${cellClass}">
                    ${formatNumber(dayTotal)}
                </th>
            `;
        }
        
        // Add grand total
        tableHtml += `
                        <th class="text-right grand-total">${formatNumber(data.totals.grandTotal)}</th>
                    </tr>
                </tfoot>
            </table>
        `;
        
        // Update the container with the table
        $('#mprTableContainer').html(tableHtml);
        
        // Initialize DataTable
        const table = $('#mprTable').DataTable({
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
                    title: `${data.period.month} ${data.period.year} Collection Summary`,
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
