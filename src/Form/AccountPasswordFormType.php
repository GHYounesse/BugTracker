<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Change password while signed in: unlike the reset flow, this asks for the
 * current password first.
 */
class AccountPasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Current password',
                'mapped' => false,
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control', 'autocomplete' => 'current-password'],
                'constraints' => [
                    new NotBlank(message: 'Enter your current password.'),
                    new UserPassword(message: 'That is not your current password.'),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'The password fields must match.',
                'first_options' => [
                    'label' => 'New password',
                    'label_attr' => ['class' => 'form-label'],
                    'attr' => ['class' => 'form-control', 'autocomplete' => 'new-password'],
                    'help' => 'At least 6 characters.',
                ],
                'second_options' => [
                    'label' => 'Repeat new password',
                    'label_attr' => ['class' => 'form-label'],
                    'attr' => ['class' => 'form-control', 'autocomplete' => 'new-password'],
                ],
                'constraints' => [
                    new NotBlank(message: 'Enter a new password.'),
                    new Length(min: 6, max: 4096, minMessage: 'Your password should be at least {{ limit }} characters'),
                ],
            ])
        ;
    }
}
