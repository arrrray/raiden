<?php

namespace App\Admin\Core\Validator;

use App\Admin\Core\Service\CurrentUser;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Package\ApiBundle\Repository\ApiServiceEntityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * UniqueEntity for Single|Multiple Column.
 */
class UniqueEntityConstraintValidator extends ConstraintValidator
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly CurrentUser $currentUser)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEntityConstraint) {
            throw new UnexpectedTypeException($constraint, UniqueEntityConstraint::class);
        }

        if (!$value) {
            return;
        }

        $fields = !is_array($constraint->fields) ? [$constraint->fields] : $constraint->fields;
        $criteria = Criteria::create();
        foreach ($fields as $columnName => $field) {
            if (!is_numeric($columnName)) {
                $criteria->andWhere(Criteria::expr()->eq($columnName, $this->context->getObject()->{$field}));
            } else {
                $criteria->andWhere(Criteria::expr()->eq($field, $this->context->getObject()->{$field}));
            }
        }

        // Edit Mode Exclude ID
        $id = $this->context->getObject()?->getId();
        if ($id) {
            if ('currentUser' === $id) {
                $id = $this->currentUser->get()->getId();
            }

            $criteria->andWhere(Criteria::expr()->neq('id', $id));
        }

        /** @var ApiServiceEntityRepository $repo */
        $repo = $this->entityManager->getRepository($constraint->entityClass); // @phpstan-ignore-line
        $total = $repo->countBy($criteria);

        if ($total > 0) {
            $this->context->addViolation($constraint->message);
        }
    }
}
