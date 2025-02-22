<?php

namespace App\Admin\Notification\Controller;

use App\Admin\Core\Entity\User;
use App\Admin\Notification\Dto\FcmRegisterDto;
use App\Admin\Notification\Dto\NotificationDto;
use App\Admin\Notification\Entity\Device;
use App\Admin\Notification\Permission\DevicePermission;
use App\Admin\Notification\Repository\DeviceRepository;
use App\Admin\Notification\Resource\DeviceResource;
use App\Admin\Notification\Service\NotificationPusher;
use Cesurapp\ApiBundle\AbstractClass\ApiController;
use Cesurapp\ApiBundle\Response\ApiResponse;
use Cesurapp\ApiBundle\Thor\Attribute\Thor;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Firebase Cloud Messaging Device Controller.
 */
class DeviceController extends ApiController
{
    #[Thor(
        stack: 'Notification',
        title: 'Register Device to Firebase Cloud Messaging',
        dto: FcmRegisterDto::class,
        order: 10
    )]
    #[Route(path: '/v1/main/notification/fcm-register', methods: ['POST'])]
    public function register(#[CurrentUser] User $user, FcmRegisterDto $dto, DeviceRepository $repo): ApiResponse
    {
        // Check
        if (!$repo->check($dto->validated('token'), $dto->validated('device'))) {
            $repo->register($dto, $user);
        }

        return ApiResponse::create()->addMessage('Operation successful');
    }

    #[Thor(
        stack: 'Notification Devices',
        title: 'List Devices',
        response: [200 => ['data' => DeviceResource::class]],
        isPaginate: true,
        order: 1
    )]
    #[Route(path: '/v1/admin/notification/device', methods: ['GET'])]
    #[IsGranted(DevicePermission::ROLE_DEVICE_LIST->value)]
    public function list(DeviceRepository $repo): ApiResponse
    {
        $query = $repo->createQueryBuilder('q');

        return ApiResponse::create()
            ->setQuery($query)
            ->setPaginate()
            ->setResource(DeviceResource::class);
    }

    #[Thor(
        stack: 'Notification Devices',
        title: 'Delete Device',
        order: 2
    )]
    #[Route(path: '/v1/admin/notification/device/{id}', methods: ['DELETE'])]
    #[IsGranted(DevicePermission::ROLE_DEVICE_DELETE->value)]
    public function delete(Device $device, DeviceRepository $repo): ApiResponse
    {
        $repo->remove($device);

        return ApiResponse::create()->addMessage('Operation successful');
    }

    #[Thor(
        stack: 'Notification Devices',
        title: 'Send Notification to Device',
        dto: NotificationDto::class,
        order: 3
    )]
    #[Route(path: '/v1/admin/notification/device/{id}', methods: ['POST'])]
    #[IsGranted(DevicePermission::ROLE_DEVICE_SEND->value)]
    public function send(Device $device, NotificationDto $dto, NotificationPusher $pusher): ApiResponse
    {
        // Send
        $pusher->onlySend($device, $dto->initObject());

        return ApiResponse::create()->addMessage('Operation successful');
    }
}
