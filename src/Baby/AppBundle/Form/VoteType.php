<?php

namespace Baby\AppBundle\Form;

use Baby\AppBundle\Entity\Vote;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class VoteType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('vote', 'choice', array('label' => 'Keuze', 'required' => true, 'choices' => array(Vote::VOTE_BOY => 'Jongen', Vote::VOTE_GIRL => 'Meisje'), 'expanded' => true))
            ->add('email', 'email', array('label' => 'E-mail', 'required' => true))
            ->add('firstname', 'text', array('label' => 'Voornaam', 'required' => true))
            ->add('lastname', 'text', array('label' => 'Achternaam', 'required' => true))
            ->add('save', 'submit', array('label' => 'Stem'));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Baby\AppBundle\Entity\Vote'
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'baby_appbundle_vote';
    }
}
