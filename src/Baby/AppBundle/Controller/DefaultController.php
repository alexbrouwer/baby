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
        $em = $this->get('doctrine.orm.entity_manager');
        $voteRepository = $em->getRepository('BabyAppBundle:Vote');

        $groupedVotes = $voteRepository->getVotesCountGroupedByVote();

        $totalVotes = 0;
        $boyVotes = 0;
        $girlVotes = 0;
        foreach ($groupedVotes as $groupedVote) {
            if ($groupedVote['vote'] == Vote::VOTE_BOY) {
                $boyVotes = $groupedVote['num'];
            }
            if ($groupedVote['vote'] == Vote::VOTE_GIRL) {
                $girlVotes = $groupedVote['num'];
            }

            $totalVotes += $groupedVote['num'];
        }

        $expectedDate = new \DateTime('2015-12-04');
        $endDate = clone($expectedDate);
        $endDate->setTime(0,0,0);
        $endDate->modify('-9 months, -5 days');
        $dateDiff = $endDate->diff(new \DateTime());

        $numWeeks = round($dateDiff->days / 7, 1);
        $numMonths = round($dateDiff->days / 30.5, 1);

        $data = array(
            'boy_percentage' => (int)round(($boyVotes / $totalVotes) * 100, 0),
            'girl_percentage' => (int)round(($girlVotes / $totalVotes) * 100, 0),
            'total_votes' => $totalVotes,
            'days_pregnant' => $dateDiff->days,
            'weeks_pregnant' => $numWeeks,
            'months_pregnant' => $numMonths,
            'overdue' => ((new \DateTime())->modify('+1 day') > $expectedDate),
        );

        return $data;
    }

    /**
     * @Route("/stem")
     * @Template()
     */
    public function voteAction(Request $request)
    {
        $vote = new Vote();

        $vote->setVote($request->query->get('vote', Vote::VOTE_BOY));

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
        // TODO send email
        return array();
    }
}
