<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ResetPasswordRequestFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'attr' => [
                'class' => '',
                'placeholder' => 'you@example.com',
                'autocomplete' => 'email',
                'autofocus' => true,
            ],
            'constraints' => [
                new NotBlank([
                    'message' => 'Please enter your email',
                ]),
                new Email([
                    'message' => 'Please enter a valid email',
                ]),
            ],
        ]);
    }
}
