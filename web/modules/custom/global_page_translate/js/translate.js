(function ($) {
  $(document).ready(function () {
    if ($('.translate-button').length === 0) {
      var button = $('<button class="translate-button">Traducir al inglés</button>');
      $('body').append(button);

      button.css({
        position: 'fixed',
        bottom: '10px',
        right: '10px',
        backgroundColor: 'black',
        color: 'white',
        padding: '10px 20px',
        border: 'none',
        borderRadius: '5px',
        cursor: 'pointer',
        zIndex: 9999
      });

      button.on('click', function () {
        const currentPath = window.location.pathname; 
        const newUrl = window.location.origin + currentPath + '?translate=en';
        window.location.href = newUrl;
      });
    }
  });
})(jQuery);
