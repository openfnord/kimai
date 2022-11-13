<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\API\Model\PageAction;
use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Event\PageActionsEvent;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/actions')]
#[Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")]
#[OA\Tag(name: 'Actions')]
final class ActionsController extends BaseApiController
{
    public function __construct(
        private ViewHandlerInterface $viewHandler,
        private EventDispatcherInterface $dispatcher,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * @param PageActionsEvent $event
     * @param string $locale
     * @return array<PageAction>
     */
    private function convertEvent(PageActionsEvent $event, string $locale): array
    {
        $this->dispatcher->dispatch($event, $event->getEventName());

        $translator = $this->translator;

        $all = [];
        foreach ($event->getActions() as $name => $action) {
            if ($action !== null) {
                $domain = \array_key_exists('translation_domain', $action) ? $action['translation_domain'] : 'messages';
                if (!\array_key_exists('title', $action)) {
                    $action['title'] = $translator->trans($name, [], $domain, $locale);
                } else {
                    $action['title'] = $translator->trans($action['title'], [], $domain, $locale);
                }
            }
            $all[] = new PageAction($name, $action);
        }

        return $all;
    }

    /**
     * Get all item actions for the given Timesheet [for internal use]
     */
    #[OA\Response(response: 200, description: 'Returns item actions for the timesheet', content: new OA\JsonContent(ref: new Model(type: PageAction::class)))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Timesheet ID to fetch', required: true)]
    #[OA\Parameter(name: 'view', in: 'path', description: 'View to display the actions at (e.g. index, custom)', required: true)]
    #[OA\Parameter(name: 'locale', in: 'path', description: 'Language to translate the action title to (e.g. de, en)', required: true)]
    #[Rest\Get(path: '/timesheet/{id}/{view}/{locale}', name: 'get_timesheet_actions', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function getTimesheetActions(Timesheet $timesheet, string $view, string $locale): Response
    {
        $event = new PageActionsEvent($this->getUser(), ['timesheet' => $timesheet], 'timesheet', $view);
        $actions = $this->convertEvent($event, $locale);

        $view = new View($actions, 200);

        return $this->viewHandler->handle($view);
    }

    /**
     * Get all item actions for the given Activity [for internal use]
     */
    #[OA\Response(response: 200, description: 'Returns item actions for the activity', content: new OA\JsonContent(ref: new Model(type: PageAction::class)))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Activity ID to fetch', required: true)]
    #[OA\Parameter(name: 'view', in: 'path', description: 'View to display the actions at (e.g. index, custom)', required: true)]
    #[OA\Parameter(name: 'locale', in: 'path', description: 'Language to translate the action title to (e.g. de, en)', required: true)]
    #[Rest\Get(path: '/activity/{id}/{view}/{locale}', name: 'get_activity_actions', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function getActivityActions(Activity $activity, string $view, string $locale): Response
    {
        $event = new PageActionsEvent($this->getUser(), ['activity' => $activity], 'activity', $view);
        $actions = $this->convertEvent($event, $locale);

        $view = new View($actions, 200);

        return $this->viewHandler->handle($view);
    }

    /**
     * Get all item actions for the given Project [for internal use]
     */
    #[OA\Response(response: 200, description: 'Returns item actions for the project', content: new OA\JsonContent(ref: new Model(type: PageAction::class)))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Project ID to fetch', required: true)]
    #[OA\Parameter(name: 'view', in: 'path', description: 'View to display the actions at (e.g. index, custom)', required: true)]
    #[OA\Parameter(name: 'locale', in: 'path', description: 'Language to translate the action title to (e.g. de, en)', required: true)]
    #[Rest\Get(path: '/project/{id}/{view}/{locale}', name: 'get_project_actions', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function getProjectActions(Project $project, string $view, string $locale): Response
    {
        $event = new PageActionsEvent($this->getUser(), ['project' => $project], 'project', $view);
        $actions = $this->convertEvent($event, $locale);

        $view = new View($actions, 200);

        return $this->viewHandler->handle($view);
    }

    /**
     * Get all item actions for the given Customer [for internal use]
     */
    #[OA\Response(response: 200, description: 'Returns item actions for the customer', content: new OA\JsonContent(ref: new Model(type: PageAction::class)))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'Customer ID to fetch', required: true)]
    #[OA\Parameter(name: 'view', in: 'path', description: 'View to display the actions at (e.g. index, custom)', required: true)]
    #[OA\Parameter(name: 'locale', in: 'path', description: 'Language to translate the action title to (e.g. de, en)', required: true)]
    #[Rest\Get(path: '/customer/{id}/{view}/{locale}', name: 'get_customer_actions', requirements: ['id' => '\d+'])]
    #[ApiSecurity(name: 'apiUser')]
    #[ApiSecurity(name: 'apiToken')]
    public function getCustomerActions(Customer $customer, string $view, string $locale): Response
    {
        $event = new PageActionsEvent($this->getUser(), ['customer' => $customer], 'customer', $view);
        $actions = $this->convertEvent($event, $locale);

        $view = new View($actions, 200);

        return $this->viewHandler->handle($view);
    }
}