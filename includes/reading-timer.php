<!-- Reading Timer & Nudge System -->
<script src="js/reading-timer.js" defer></script>

<!-- Initialize with user preferences if logged in -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if user has disabled nudges in their profile
    <?php if (isset($_SESSION['user_id'])): ?>
    // You can load user preferences from database here
    // For now, check localStorage
    if (localStorage.getItem('disableNudges') === 'true') {
        console.log('Nudges are disabled for this user');
    }
    <?php endif; ?>
});
</script>