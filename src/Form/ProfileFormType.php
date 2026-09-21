<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('displayName', TextType::class, [
                'label' => 'Display name',
                'required' => false,
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control', 'maxlength' => 80, 'autocomplete' => 'name'],
                'help' => 'Shown next to your username. Leave empty to show the username only.',
                'constraints' => [new Length(max: 80)],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control', 'autocomplete' => 'email'],
                'help' => 'Used to send you a password reset link.',
                'constraints' => [new NotBlank(message: 'Enter an email address.')],
            ])
            ->add('avatar', FileType::class, [
                'label' => 'Photo',
                'mapped' => false,
                'required' => false,
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control', 'accept' => 'image/jpeg,image/png,image/webp'],
                'help' => 'JPEG, PNG or WebP, up to 2 MB.',
                'constraints' => [
                    new File(
                        maxSize: '2M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Upload a JPEG, PNG or WebP image.',
                    ),
                ],
            ])
            ->add('removeAvatar', CheckboxType::class, [
                'label' => 'Remove current photo',
                'mapped' => false,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
