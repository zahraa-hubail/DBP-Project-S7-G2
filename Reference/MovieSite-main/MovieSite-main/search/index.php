<?php
session_start();
$base_path = "../";
$v = filemtime(__FILE__); // cache-bust version
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Movies — The Binge Box</title>
    <link rel="stylesheet" href="../shared.css?v=<?= $v ?>">
    <link rel="stylesheet" href="search.css?v=<?= $v ?>">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body>

<?php include "../includes/navbar.php"; ?>

<main>

    <h1 class="page-title">Search Movies</h1>

    <div class="search-controls">
        <input  type="text"   id="search"   placeholder="Search by title, description or director..." />
        <input  type="text"   id="creator"  placeholder="Filter by creator..." />
        <select id="year">
            <option value="">All Years</option>
            <?php for ($i = 2026; $i >= 1980; $i--): ?>
            <option value="<?= $i ?>"><?= $i ?></option>
            <?php endfor; ?>
        </select>
        <select id="min_rating">
            <option value="0">Any Rating</option>
            <option value="1">&#9733; 1+</option>
            <option value="2">&#9733; 2+</option>
            <option value="3">&#9733; 3+</option>
            <option value="4">&#9733; 4+</option>
            <option value="4.5">&#9733; 4.5+</option>
        </select>
        <select id="sort">
            <option value="">Default (Newest)</option>
            <option value="rating_desc">Highest Rated</option>
            <option value="rating_asc">Lowest Rated</option>
            <option value="latest">Latest Releases</option>
            <option value="oldest">Oldest Releases</option>
        </select>
        <button id="search-btn" class="search-btn" type="button">Search</button>
    </div>

    <div id="search-results">
        <p class="loading-msg">Loading movies&hellip;</p>
    </div>

</main>

<footer>
    <p>&copy; 2026 The Binge Box. All rights reserved.</p>
</footer>

<script>
$(document).ready(function () {

    var currentPage = 1;

    function loadResults(page) {
        currentPage = page || 1;
        $('#search-btn').prop('disabled', true).text('Searching…');
        $.ajax({
            type: 'GET',
            url:  'search.php',
            data: {
                search:     $('#search').val(),
                creator:    $('#creator').val(),
                year:       $('#year').val(),
                min_rating: $('#min_rating').val(),
                sort:       $('#sort').val(),
                page:       currentPage
            },
            success: function (html) {
                $('#search-results').html(html || '<p class="no-results">No results found.</p>');
            },
            error: function () {
                $('#search-results').html('<p class="no-results">Error loading results. Please try again.</p>');
            },
            complete: function () {
                $('#search-btn').prop('disabled', false).text('Search');
            }
        });
    }

    /* Pre-fill from homepage hero (?init=...) and trigger search */
    var params = new URLSearchParams(window.location.search);
    var init   = params.get('init');
    if (init) {
        $('#search').val(init);
        if (window.history.replaceState) {
            window.history.replaceState({}, '', window.location.pathname);
        }
    }

    /* Always load on page open — shows all DB movies when blank */
    loadResults();

    /* Pagination click — delegated since content is injected by AJAX */
    $(document).on('click', '#db-pagination .page-btn:not(.disabled), #db-pagination .page-num', function (e) {
        e.preventDefault();
        var p = parseInt($(this).data('page'), 10);
        if (!isNaN(p)) { loadResults(p); $('html,body').animate({scrollTop: $('#search-results').offset().top - 80}, 200); }
    });

    /* Live-search resets to page 1 */
    var debounce;
    $('#search, #creator').on('input', function () {
        clearTimeout(debounce);
        debounce = setTimeout(function(){ loadResults(1); }, 400);
    });

    /* Instant on dropdown change or button click — reset to page 1 */
    $('#year, #sort, #min_rating').on('change', function(){ loadResults(1); });
    $('#search-btn').on('click',   function(){ loadResults(1); });

    /* Enter key in search inputs */
    $('#search, #creator').on('keydown', function (e) {
        if (e.key === 'Enter') { clearTimeout(debounce); loadResults(1); }
    });
});
</script>

</body>
</html>
