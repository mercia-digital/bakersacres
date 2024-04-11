jQuery(document).ready(function ($) {
    $('button.hamburger').click(function () {
        $(this).toggleClass('is-active');
        $('#masthead .mobile-nav').toggleClass('open');
    });
});

jQuery(document).ready(function ($) {
    $('.search-toggle').click(function () {
        $(this).toggleClass('is-active');
        $('#masthead .search-form-wrapper').toggleClass('open');
        $('#masthead').toggleClass('search-open');
    });
});

jQuery(document).ready(function ($) {
    $('#variety-list-sidebar .category-list .heading').click(function () {
        $(this).toggleClass('is-active');
        $(this).next().slideToggle();
    });
});

jQuery(document).ready(function ($) {
    $('#variety-list-sidebar .mobile-filters .heading').click(function () {
        $(this).toggleClass('is-active');
        $('#variety-list-sidebar .filters').slideToggle();
    });
});

jQuery(document).ready(function ($) {
    $('#controls .clear-filters').click(function () {
        window.location.href = window.location.href.replace(window.location.search,'');
    });
});

jQuery(document).ready(function ($) {
    $('#title-groups .title-group').click(function () {
        var letters = $(this).data('letters');

        var currentUrl = new URL(window.location.href);
        var searchParams = currentUrl.searchParams;

        searchParams.set('initial-letter', letters);

        // Construct the new URL
        var newUrl = currentUrl.origin + currentUrl.pathname + '?' + searchParams.toString();
        
        // Navigate to the new URL
        window.location.href = newUrl;
    });
});

jQuery(document).ready(function ($) {
    $('input.variety-tax-cb').change(function () {
        var tax = $(this).data('taxonomy');
        var term = $(this).data('term');
        var currentUrl = new URL(window.location.href);
        var searchParams = currentUrl.searchParams;

        // Retrieve the current values for the taxonomy or initialize an empty array
        var currentTerms = searchParams.get(tax) ? searchParams.get(tax).split(',') : [];

        if (this.checked) {
            // Add the term to the array if not already included
            if (!currentTerms.includes(term)) {
                currentTerms.push(term);
            }
        } else {
            // Remove the term from the array
            currentTerms = currentTerms.filter(function(currentTerm) {
                return currentTerm !== term;
            });
        }

        // Update the search parameter with the new array or delete it if empty
        if (currentTerms.length > 0) {
            searchParams.set(tax, currentTerms.join(','));
        } else {
            searchParams.delete(tax);
        }

        // Construct the new URL
        var newUrl = currentUrl.origin + currentUrl.pathname + '?' + searchParams.toString();
        
        // Navigate to the new URL
        window.location.href = newUrl;
    });
});

