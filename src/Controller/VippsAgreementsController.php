<?php

namespace Drupal\vipps_recurring_payments\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\vipps_recurring_payments\Entity\VippsAgreementsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class VippsAgreementsController.
 *
 *  Returns responses for Vipps agreements routes.
 */
class VippsAgreementsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Displays a Vipps agreements revision.
   *
   * @param int $vipps_agreements_revision
   *   The Vipps agreements revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($vipps_agreements_revision) {
    $vipps_agreements = $this->entityTypeManager()->getStorage('vipps_agreements')
      ->loadRevision($vipps_agreements_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('vipps_agreements');

    return $view_builder->view($vipps_agreements);
  }

  /**
   * Page title callback for a Vipps agreements revision.
   *
   * @param int $vipps_agreements_revision
   *   The Vipps agreements revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($vipps_agreements_revision) {
    $vipps_agreements = $this->entityTypeManager()->getStorage('vipps_agreements')
      ->loadRevision($vipps_agreements_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $vipps_agreements->label(),
      '%date' => $this->dateFormatter->format($vipps_agreements->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Vipps agreements.
   *
   * @param \Drupal\vipps_recurring_payments\Entity\VippsAgreementsInterface $vipps_agreements
   *   A Vipps agreements object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(VippsAgreementsInterface $vipps_agreements) {
    $account = $this->currentUser();
    $vipps_agreements_storage = $this->entityTypeManager()->getStorage('vipps_agreements');

    $langcode = $vipps_agreements->language()->getId();
    $langname = $vipps_agreements->language()->getName();
    $languages = $vipps_agreements->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $vipps_agreements->label()]) : $this->t('Revisions for %title', ['%title' => $vipps_agreements->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all vipps agreements revisions") || $account->hasPermission('administer vipps agreements entities')));
    $delete_permission = (($account->hasPermission("delete all vipps agreements revisions") || $account->hasPermission('administer vipps agreements entities')));

    $rows = [];

    $vids = $vipps_agreements_storage->revisionIds($vipps_agreements);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\vipps_recurring_payments\VippsAgreementsInterface $revision */
      $revision = $vipps_agreements_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $vipps_agreements->getRevisionId()) {
          $link = $this->l($date, new Url('entity.vipps_agreements.revision', [
            'vipps_agreements' => $vipps_agreements->id(),
            'vipps_agreements_revision' => $vid,
          ]));
        }
        else {
          $link = $vipps_agreements->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.vipps_agreements.translation_revert', [
                'vipps_agreements' => $vipps_agreements->id(),
                'vipps_agreements_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.vipps_agreements.revision_revert', [
                'vipps_agreements' => $vipps_agreements->id(),
                'vipps_agreements_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.vipps_agreements.revision_delete', [
                'vipps_agreements' => $vipps_agreements->id(),
                'vipps_agreements_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['vipps_agreements_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
