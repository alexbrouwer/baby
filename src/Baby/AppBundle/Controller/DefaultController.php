<?php

namespace Baby\AppBundle\Controller;

use Baby\AppBundle\Entity\Vote;
use Baby\AppBundle\Form\VoteType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @Route("/stem")
     * @Template()
     */
    public function voteAction(Request $request) {
        $vote = new Vote();

        $form = $this->createForm(new VoteType(), $vote);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $vote->setVotedAt(new \DateTime());

            $em = $this->get('doctrine.orm.entity_manager');
            $em->persist($vote);
            $em->flush();

            // redirect
            return $this->redirectToRoute('baby_app_default_votesaved');
        }

        return array(
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/bedankt")
     * @Template()
     */
    public function voteSavedAction()
    {
        return array();
    }
}
