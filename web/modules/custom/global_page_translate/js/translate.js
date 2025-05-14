(function ($) {
  $(document).ready(function () {
    /**
     * Añade el botón flotante para traducir.
     */
    function addTranslateButton() {
      if ($('.translate-button').length === 0) {
        var button = $('<button class="translate-button">Traducir al inglés</button>');
        $('body').append(button);

        button.css({
          position: 'fixed',
          bottom: '10px',
          right: '10px',
          backgroundColor: '#007bff',
          color: 'white',
          padding: '10px 20px',
          border: 'none',
          borderRadius: '5px',
          cursor: 'pointer',
          zIndex: 9999
        });

        button.on('click', function () {
          var langcode = 'en';
          $.ajax({
            url: '/translate-content/' + langcode,
            type: 'POST',
            dataType: 'json',
            data: {
              page_url: window.location.href
            },
            success: function (data) {
              $('body').html(data.translated);

              addTranslateButton();

              if (typeof Drupal !== 'undefined' && Drupal.attachBehaviors) {
                Drupal.attachBehaviors();
              }
            },
            error: function (jqXHR, textStatus, errorThrown) {
              console.error('AJAX error:', textStatus, errorThrown);
              alert('Error al traducir la página: ' + textStatus);
            }
          });
        });
      }
    }

    // Añadimos el botón al cargar la página
    addTranslateButton();
  });
})(jQuery);
