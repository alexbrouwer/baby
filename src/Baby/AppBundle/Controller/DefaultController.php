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
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEm()
    {
        return $this->get('doctrine.orm.entity_manager');
    }

    protected function getVoteStats()
    {
        $voteRepository = $this->getEm()->getRepository('BabyAppBundle:Vote');

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

        return array(
            'boy_percentage' => (int)round(($boyVotes / $totalVotes) * 100, 0),
            'boy_total' => $boyVotes,
            'girl_percentage' => (int)round(($girlVotes / $totalVotes) * 100, 0),
            'girl_total' => $girlVotes,

            'total_votes' => $totalVotes,
        );
    }

    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        $voteStats = $this->getVoteStats();

        $expectedDate = new \DateTime('2015-12-04');
        $endDate = clone($expectedDate);
        $endDate->setTime(0, 0, 0);
        $endDate->modify('-9 months, -5 days');
        $dateDiff = $endDate->diff(new \DateTime());

        $numWeeks = round($dateDiff->days / 7, 1);
        $numMonths = round($dateDiff->days / 30.5, 1);

        $data = array(
            'weeks_pregnant' => $numWeeks,
            'months_pregnant' => $numMonths,
            'overdue' => ((new \DateTime())->modify('+1 day') > $expectedDate),
        );

        return array_merge($voteStats, $data);
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

            $em = $this->getEm();
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

    /**
     * @Route("/stemmen")
     * @Template()
     */
    public function viewVotersAction()
    {
        $voteStats = $this->getVoteStats();

        $voteRepository = $this->getEm()->getRepository('BabyAppBundle:Vote');

        $boyVoters = $voteRepository->getVotersByVote(Vote::VOTE_BOY);
        $girlVoters = $voteRepository->getVotersByVote(Vote::VOTE_GIRL);

        if (count($boyVoters) != count($girlVoters)) {
            $longest = max($boyVoters, $girlVoters);
            $shortest = min($boyVoters, $girlVoters);
        } else {
            $longest = $boyVoters;
            $shortest = $girlVoters;
        }
        $voters = array();
        foreach ($longest as $key => $voter1) {
            $voters[$key][$voter1->getVote()] = $voter1->getFirstName() . ' ' . $voter1->getLastName();
            if (array_key_exists($key, $shortest)) {
                $voter2 = $shortest[$key];
                $voters[$key][$voter2->getVote()] = $voter2->getFirstName() . ' ' . $voter2->getLastName();
            } else {
                $voters[$key][$voter1->getVote() == Vote::VOTE_BOY ? Vote::VOTE_GIRL : Vote::VOTE_BOY] = false;
            }
        }

        $data = array(
            'voters' => $voters,
        );

        return array_merge($voteStats, $data);
    }
}
