<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Issue;
use App\Entity\Project;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class IssueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('visibility',ChoiceType::class,[
                'choices'=>[
                    'Public'=>'public',
                    'Private'=>'private',
                ],
                'multiple'=> false,
                'expanded'=> false,
                'attr'=>[
                    'class'=>'form-select'],]

            )
            ->add('priority',ChoiceType::class,[
                'choices'=>[
                    'Low'=>'low',
                    'Normal'=>'normal',
                    'High'=>'high',
                    'Urgent'=>'urgent',
                    'Immediate'=>'immediate',
                ],
                'multiple'=> false,
                'expanded'=> false,
                'attr'=>[
                    'class'=>'form-select'],]

            )
            ->add('severity',ChoiceType::class,[
                'choices'=>[
                    'Trivial'=>'trivial',
                    'Minor'=>'minor',
                    'Major'=>'major',
                    'Critical'=>'critical',
                    'Blocker'=>'blocker',
                ],
                'multiple'=> false,
                'expanded'=> false,
                'attr'=>[
                    'class'=>'form-select'],]

            )
            ->add('stepsToReproduce',TextType::class,['required'=>false,'attr'=>[
                'class'=>'form-control mb-3',
                'id'=>'floatingInput',],

            ])
            ->add('status',ChoiceType::class,[
                'choices'=>[
                    'New'=>'new',
                    'Accepted'=>'accepted',
                    'Confirmed'=>'confirmed',
                    'Assigned'=>'assigned',
                    'Processed'=>'processed',
                    'Closed'=>'closed',
                ],
                'multiple'=> false,
                'expanded'=> false,
                'attr'=>[
                    'class'=>'form-select'],]

            )
            ->add('summary',TextType::class,['attr'=>[
                'class'=>'form-control mb-3',
                'id'=>'floatingInput',],

            ])
            ->add('description',TextType::class,['attr'=>[
                'class'=>'form-control mb-3',
                'id'=>'floatingInput',],

            ])
            ->add('attachment',FileType::class,[
                'mapped'=>false,
                'required'=>false,
                'constraints'=>[
                    new File([
                        'maxSize'=>'5M',
                        'mimeTypes'=>[
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'application/pdf',
                        ],
                        'mimeTypesMessage'=>'Please upload a valid image (JPEG, PNG, GIF) or PDF file.',
                    ]),
                ],
                'attr'=>[
                'class'=>'form-control',],
            ])
            ->add('project',EntityType::class,['class'=>Project::class,'choice_label'=>'name','attr'=>[
                'class'=>'form-select'],])
            ->add('category',EntityType::class,['class'=>Category::class,'choice_label'=>'name','attr'=>[
                'class'=>'form-select'],])
            ->add('assigned',EntityType::class,['class'=>User::class,'choice_label'=>'username','required'=>false,'attr'=>[
                'class'=>'form-select'],])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Issue::class,
        ]);
    }
}
