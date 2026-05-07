<!-- Loader Overlay -->
{{-- <div id="loader">
    <div class="loader-content">
        <div class="spinner">
        </div>
        <div class="loading-text">eDocs Loading...</div>
    </div>
</div> --}}

<!-- Styles -->
<style>
    #loader {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.3);
        z-index: 99999;
        display: none;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        text-align: center;
    }

    .loader-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .spinner {
        width: 70px;
        height: 70px;
        border: 8px solid #f3f3f3;
        border-top: 8px solid green;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    /* .spinner-img {
        width: 40px;
        height: 40px;
        object-fit: contain;
        pointer-events: none;
        user-select: none;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    } */

    .loading-text {
        margin-top: 10px;
        font-size: 22px;
        font-weight: bold;
        color: green;
        background-color: white;
        padding: 8px;
        border-radius: 5px;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<!-- Loader Logic -->
<script>
    let loaderTimeout;
    let pageLoadTimeout;

    // Show loader after 1 second delay
    function showLoaderWithDelay(delay = 1000) {
        loaderTimeout = setTimeout(() => {
            $('#loader').fadeIn(200);
        }, delay);
    }

    // Hide loader
    function hideLoader() {
        clearTimeout(loaderTimeout);
        $('#loader').fadeOut(200);
    }

    $(document).ready(function() {
        // Page load timer
        pageLoadTimeout = setTimeout(function() {
            showLoaderWithDelay(1000);
        }, 1000);

        $(document).ajaxStart(function() {
            showLoaderWithDelay(1000);
        });

        $(document).ajaxStop(function() {
            hideLoader();
        });

        // Form submission loader
        $('form').on('submit', function() {
            showLoaderWithDelay(1000);
        });

        // Link click handling
        $(document).on('click', 'a', function(e) {
            if ($(this).attr('href') && !$(this).attr('href').startsWith('#') && !$(this).attr('href')
                .startsWith('mailto:')) {
                showLoaderWithDelay(1000);
            }
        });

        // Before unload
        $(window).on('beforeunload', function() {
            showLoaderWithDelay(1000);
        });

        // After load
        $(window).on('load', function() {
            clearTimeout(pageLoadTimeout);
            hideLoader();
        });

        // Back/Forward Cache
        $(window).on('pageshow', function(event) {
            if (event.originalEvent.persisted) {
                $('#loader').fadeOut(300);
            }
        });
    });

    // Axios interceptors
    if (typeof axios !== 'undefined') {
        axios.interceptors.request.use(function(config) {
            showLoaderWithDelay(1000);
            return config;
        }, function(error) {
            hideLoader();
            return Promise.reject(error);
        });

        axios.interceptors.response.use(function(response) {
            hideLoader();
            return response;
        }, function(error) {
            hideLoader();
            return Promise.reject(error);
        });
    }
</script>
