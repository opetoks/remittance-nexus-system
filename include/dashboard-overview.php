<!-- Dashboard Overview -->
<div class="stats-grid">
<div class="stat-card">
    <div class="stat-card-title">Today's Collection</div>
    <div class="stat-card-value"><?php echo isset($stats['today']['total']) ? formatCurrency($stats['today']['total']) : 0; ?></div>
    <div class="stat-card-text"><?php echo isset($stats['today']['count']) ? $stats['today']['count'] : 0; ?> transactions</div>
</div>

<div class="stat-card">
    <div class="stat-card-title">This Week</div>
    <div class="stat-card-value"><?php echo isset($stats['week']['total']) ? formatCurrency($stats['week']['total']) : 0; ?></div>
    <div class="stat-card-text"><?php echo isset($stats['week']['count']) ? $stats['week']['count'] : 0 ?> transactions</div>
</div>

<div class="stat-card">
    <div class="stat-card-title">This Month</div>
    <div class="stat-card-value"><?php echo isset($stats['month']['total']) ? formatCurrency($stats['month']['total']) : 0; ?></div>
    <div class="stat-card-text"><?php echo isset($stats['month']['count']) ? $stats['month']['count'] : 0; ?> transactions</div>
</div>

<div class="stat-card">
    <div class="stat-card-title">Pending Approvals</div>
    <div class="stat-card-value"><?php echo count($pendingTransactions); ?></div>
    <div class="stat-card-text">Waiting for your action</div>
</div>
</div>