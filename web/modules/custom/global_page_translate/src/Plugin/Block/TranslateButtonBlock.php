namespace Drupal\global_page_translate\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Translate Button' block.
 *
 * @Block(
 *   id = "global_translate_button",
 *   admin_label = @Translation("Botón de traducción global"),
 * )
 */
class TranslateButtonBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'global_page_translate_button',
    ];
  }

}
