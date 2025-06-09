<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Message;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Your Message',
                'attr' => [
                    'placeholder' => 'Enter your secure message here...',
                    'rows' => 6
                ]
            ])
            ->add('expiresAt', ChoiceType::class, [
                'label' => 'Message Expires After',
                'choices' => [
                    '1 hour' => '1 hour',
                    '6 hours' => '6 hours',
                    '12 hours' => '12 hours',
                    '24 hours' => '24 hours',
                    '48 hours' => '48 hours',
                    '1 week' => '1 week'
                ],
                'data' => '24 hours'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
        ]);
    }
} 