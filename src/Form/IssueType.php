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

class IssueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('visibilite',ChoiceType::class,[
                'choices'=>[
                    'public'=>'public',
                    'private'=>'private',
                ],
                'multiple'=> false,
                'expanded'=> false,
                'attr'=>[
                    'class'=>'form-select'],]
               
            )
            ->add('priorite',ChoiceType::class,[
                'choices'=>[
                    'basse'=>'basse',
                    'normale'=>'normale',
                    'élevée'=>'élevée',
                    'urgente'=>'urgente',
                    'immediate'=>'immediate',
                ],
                'multiple'=> true ,
                'expanded'=> true,
                'multiple'=> false,
                'expanded'=> false,
                'attr'=>[
                    'class'=>'form-select'],]
               
            )
            ->add('severite',ChoiceType::class,[
                'choices'=>[
                    'simple'=>'simple',
                    'mineur'=>'mineur',
                    'majeur'=>'majeur',
                    'critique'=>'critique',
                    'bloquant'=>'bloquant',
                ],
                'multiple'=> false,
                'expanded'=> false,
                'attr'=>[
                    'class'=>'form-select'],]
               
            )
            ->add('reproduce',TextType::class,['attr'=>[
                'class'=>'form-control mb-3',
                'id'=>'floatingInput',],
            
            ])
            ->add('etat',ChoiceType::class,[
                'choices'=>[
                    'nouveau'=>'nouveau',
                    'accepté'=>'accepté',
                    'confirmé'=>'confirmé',
                    'affecté'=>'affecté',
                    'traité'=>'traité',
                    'fermé'=>'fermé',
                ],
                'multiple'=> false,
                'expanded'=> false,
                'attr'=>[
                    'class'=>'form-select'],]
               
            )
            ->add('resume',TextType::class,['attr'=>[
                'class'=>'form-control mb-3',
                'id'=>'floatingInput',],
            
            ])
            ->add('description',TextType::class,['attr'=>[
                'class'=>'form-control mb-3',
                'id'=>'floatingInput',],
            
            ])
            ->add('tags',FileType::class,[
                'mapped'=>false,
                'required'=>false,
                'attr'=>[
                'class'=>'form-control',],
            ])
            ->add('project',EntityType::class,['class'=>Project::class,'choice_label'=>'name','attr'=>[
                'class'=>'form-select'],])
            ->add('category',EntityType::class,['class'=>Category::class,'choice_label'=>'name','attr'=>[
                'class'=>'form-select'],])
            ->add('assigned',EntityType::class,['class'=>User::class,'choice_label'=>'username','attr'=>[
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
