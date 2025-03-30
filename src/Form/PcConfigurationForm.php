<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PcConfigurationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('cpu', ChoiceType::class, [
                'choices' => [$options['cpus']],
                'choices_label' => function ($cpu) {
                    return $cpu->getName() . '-' . $cpu->getModel();
                },
                'choice_value' => 'id'
            ]);
        $builder
            ->add('motherboard', ChoiceType::class, [
                'choices' => [$options['motherboards']],
                'choices_label' => function ($motherboard) {
                    return $motherboard->getName() . '-' . $motherboard->getModel();
                },
                'choice_value' => 'id'
            ]);
        $builder
            ->add('ram', ChoiceType::class, [
                'choices' => [$options['rams']],
                'choices_label' => function ($ram) {
                    return $ram->getName() . '-' . $ram->getModel();
                },
                'choice_value' => 'id'
            ]);
        $builder
            ->add('gpu', ChoiceType::class, [
                'choices' => [$options['gpus']],
                'choices_label' => function ($gpu) {
                    return $gpu->getName() . '-' . $gpu->getModel();
                },
                'choice_value' => 'id'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'cpus' => [],
            'gpus' => [],
            'motherboards' => [],
            'rams' => [],
            'psus' => [],
        ]);
    }
}
