<?php

namespace App\Admin\Core\EventListener;

use App\Admin\Core\Entity\OtpKey;
use App\Admin\Core\Enum\OtpType;
use App\Admin\Core\Service\MailPusher;
use App\Admin\Core\Service\SmsPusher;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * OTP Created Listener.
 *
 * Send Email | SMS.
 */
#[Autoconfigure(tags: [
    [
        'name' => 'doctrine.orm.entity_listener',
        'event' => 'postPersist',
        'entity' => OtpKey::class,
        'lazy' => true,
    ],
])]
readonly class OtpKeyListener
{
    public function __construct(
        private SmsPusher $smsPusher,
        private MailPusher $mailPusher,
        private TranslatorInterface $translator
    ) {
    }

    public function postPersist(OtpKey $otpKey, LifecycleEventArgs $event): void
    {
        match ($otpKey->getType()->value) {
            OtpType::EMAIL->value => $this->sendMail($otpKey),
            OtpType::PHONE->value => $this->sendPhone($otpKey),
        };
    }

    private function sendMail(OtpKey $otpKey): void
    {
        $this->mailPusher->send(
            (new Email())
                ->to($otpKey->getAddress() ?? $otpKey->getOwner()->getEmail())
                ->subject('Verification Code')
                ->text($this->translator->trans('Verification code: %otpkey%', ['%otpkey%' => $otpKey->getOtpKey()]))
        );
    }

    private function sendPhone(OtpKey $otpKey): void
    {
        $phone = $otpKey->getAddress() ? (int) $otpKey->getAddress() : $otpKey->getOwner()->getPhone();

        $this->smsPusher->send(
            $phone,
            $otpKey->getOwner()->getPhoneCountry(),
            $this->translator->trans('Verification code: %otpkey%', ['%otpkey%' => $otpKey->getOtpKey()])
        );
    }
}
