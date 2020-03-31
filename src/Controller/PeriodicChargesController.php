<?php

namespace Drupal\vipps_recurring_payments\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\vipps_recurring_payments\Entity\PeriodicChargesInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PeriodicChargesController.
 *
 *  Returns responses for Periodic charges routes.
 */
class PeriodicChargesController extends ControllerBase implements ContainerInjectionInterface {

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
   * Displays a Periodic charges revision.
   *
   * @param int $periodic_charges_revision
   *   The Periodic charges revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionShow($periodic_charges_revision) {
    $periodic_charges = $this->entityTypeManager()->getStorage('periodic_charges')
      ->loadRevision($periodic_charges_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('periodic_charges');

    return $view_builder->view($periodic_charges);
  }

  /**
   * Page title callback for a Periodic charges revision.
   *
   * @param int $periodic_charges_revision
   *   The Periodic charges revision ID.
   *
   * @return string
   *   The page title.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionPageTitle($periodic_charges_revision) {
    $periodic_charges = $this->entityTypeManager()->getStorage('periodic_charges')
      ->loadRevision($periodic_charges_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $periodic_charges->label(),
      '%date' => $this->dateFormatter->format($periodic_charges->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Periodic charges.
   *
   * @param \Drupal\vipps_recurring_payments\Entity\PeriodicChargesInterface $periodic_charges
   *   A Periodic charges object.
   *
   * @return array
   *   An array as expected by drupal_render().
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionOverview(PeriodicChargesInterface $periodic_charges) {
    $account = $this->currentUser();
    $periodic_charges_storage = $this->entityTypeManager()->getStorage('periodic_charges');

    $build['#title'] = $this->t('Revisions for %title', ['%title' => $periodic_charges->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all periodic charges revisions") || $account->hasPermission('administer periodic charges entities')));
    $delete_permission = (($account->hasPermission("delete all periodic charges revisions") || $account->hasPermission('administer periodic charges entities')));

    $rows = [];

    $vids = $periodic_charges_storage->revisionIds($periodic_charges);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\vipps_recurring_payments\PeriodicChargesInterface $revision */
      $revision = $periodic_charges_storage->loadRevision($vid);
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $periodic_charges->getRevisionId()) {
          $link = $this->l($date, new Url('entity.periodic_charges.revision', [
            'periodic_charges' => $periodic_charges->id(),
            'periodic_charges_revision' => $vid,
          ]));
        }
        else {
          $link = $periodic_charges->link($date);
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
              'url' => Url::fromRoute('entity.periodic_charges.revision_revert', [
                'periodic_charges' => $periodic_charges->id(),
                'periodic_charges_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.periodic_charges.revision_delete', [
                'periodic_charges' => $periodic_charges->id(),
                'periodic_charges_revision' => $vid,
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

    $build['periodic_charges_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
