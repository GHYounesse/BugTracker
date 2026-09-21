<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Issue;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
            ->add('stepsToReproduce',TextareaType::class,['required'=>false,'attr'=>[
                'class'=>'form-control',
                'rows'=>4,],
            ])
            ->add('summary',TextType::class,['attr'=>[
                'class'=>'form-control',
                'maxlength'=>255,],
            ])
            ->add('description',TextareaType::class,['attr'=>[
                'class'=>'form-control',
                'rows'=>6,],
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
                'class'=>'form-select'],
                'query_builder'=>function (ProjectRepository $repository) use ($options) {
                    $user = $options['user'];
                    if (!$user || in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                        return $repository->createQueryBuilder('p')->orderBy('p.name', 'ASC');
                    }

                    return $repository->createQueryBuilder('p')
                        ->join('p.members', 'pm')
                        ->andWhere('pm.user = :user')
                        ->setParameter('user', $user)
                        ->orderBy('p.name', 'ASC');
                },
            ])
            ->add('category',EntityType::class,['class'=>Category::class,'choice_label'=>'name','attr'=>[
                'class'=>'form-select'],])
        ;

        // status/assignee are triage decisions: hide them from the form entirely
        // (not just visually) for members who aren't a project admin/manager, so
        // the fields can't be tampered with via a raw POST either
        if ($options['canManage']) {
            $builder->add('status',ChoiceType::class,[
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

            );
            // deactivated users can't take new work; keep the current assignee
            // selectable so editing an old issue doesn't fail validation
            $current = $options['data'] instanceof Issue ? $options['data']->getAssigned() : null;
            $builder->add('assigned',EntityType::class,[
                'class'=>User::class,
                'choice_label'=>'username',
                'required'=>false,
                'attr'=>['class'=>'form-select'],
                'query_builder'=>function (UserRepository $repository) use ($current) {
                    $qb = $repository->createQueryBuilder('u')
                        ->andWhere('u.active = true')
                        ->orderBy('u.username', 'ASC');
                    if ($current) {
                        $qb->orWhere('u = :current')->setParameter('current', $current);
                    }

                    return $qb;
                },
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Issue::class,
            'user' => null,
            'canManage' => true,
        ]);
        $resolver->setAllowedTypes('user', [User::class, 'null']);
        $resolver->setAllowedTypes('canManage', 'bool');
    }
}
