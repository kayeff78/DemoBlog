<?php

namespace App\Form;

use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CommentaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('auteur', TextType::class, [
                'label' => "Nom de l'auteur",
                'required' => false,
                'attr' => ["placeholder" => "Saisir un nom"],
                'constraints' => [
                    new NotBlank([
                        'message' => "Merci de saisir un nom"
                    ])
                ]
            ])
            ->add('commentaire', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => "Saisir le contenu du commentaire",
                    'rows' => "10"
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => "Merci de saisir un commentaire"
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class,
        ]);
    }
}
