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
            return $this->redirectToRoute(
                'baby_app_default_votesaved',
                array('vote' => $vote->getVote(), 'first_name' => $vote->getFirstname())
            );
        }

        return array(
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/bedankt")
     * @Template()
     *
     * @param Request $request
     * @return array
     */
    public function voteSavedAction(Request $request)
    {
        $voteStats = $this->getVoteStats();

        $vote = $request->query->get('vote');
        $voteTotal = $voteStats[$vote . '_total'];
        $votePercentage = $voteStats[$vote . '_percentage'];

        return array(
            'vote' => $vote,
            'vote_total' => $voteTotal,
            'vote_percentage' => $votePercentage,
            'first_name' => $request->query->get('first_name')
        );
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

    /**
     * @Route("/manage")
     * @Template()
     */
    public function manageAction(Request $request)
    {
        $voteRepository = $this->getEm()->getRepository('BabyAppBundle:Vote');

        $voters = $voteRepository->findBy(array(), array('lastname' => 'ASC', 'firstname' => 'ASC'));

        if ($request->isMethod(Request::METHOD_POST)) {
            $activationIds = $request->request->get('switch', []);
            foreach ($voters as $voter) {
                if (in_array($voter->getId(), $activationIds)) {
                    if (!$voter->getActivationKey()) {
                        $this->activateVoter($voter);
                    }
                } else {
                    $this->activateVoter($voter, true);
                }
            }
        }

        return array(
            'voters' => $voters
        );
    }

    /**
     * @Route("/manage/resend/{id}")
     *
     * @param int $id
     * @return array
     */
    public function manageResendAction($id)
    {
        $voter = $this->getEm()->find('BabyAppBundle:Vote', $id);
        if ($voter && $voter->getActivationKey()) {
            $this->activateVoter($voter);
        }

        return $this->redirectToRoute('baby_app_default_manage');
    }

    protected function activateVoter(Vote $voter, $deactivate = false)
    {
        $em = $this->getEm();

        if (!$deactivate) {
            $voteRepository = $em->getRepository('BabyAppBundle:Vote');

            $fullKey = md5($voter->getEmail() . microtime(false));

            $keyStart = 0;
            $key = substr($fullKey, $keyStart, 8);
            while ($voteRepository->findOneBy(array('activationKey' => $key)) && $keyStart + 8 <= strlen($fullKey)) {
                $keyStart++;
                $key = substr($fullKey, $keyStart, 8);
            }
        } else {
            $key = null;
        }

        $voter->setActivationKey($key);
        $em->persist($voter);
        $em->flush();

        if ($voter->getActivationKey()) {
            $message = \Swift_Message::newInstance();
            $message->setSubject('Baby Brouwer: Wat wordt het?');
            $message->setFrom('brouwer.alexander@gmail.com');
            $message->setTo($voter->getEmail());
            $message->setBody(
                $this->renderView('BabyAppBundle:Emails:activate.html.twig', array('voter' => $voter)),
                'text/html'
            );
//            $message->addPart(
//                $this->renderView('BabyAppBundle:Emails:activate.txt.twig', array('voter' => $voter)),
//                'text/plain'
//            );

            $this->get('mailer')->send($message);
        }
    }

    /**
     * @Route("/watwordthet")
     * @Template()
     */
    public function showAction(Request $request)
    {
        $activationKey = $request->query->get('key', false);
        $voteRepository = $this->getEm()->getRepository('BabyAppBundle:Vote');
        $voter = null;
        if ($activationKey) {
            $voter = $voteRepository->findOneBy(array('activationKey' => $activationKey));
        }

        if (!$voter) {
            return $this->redirectToRoute('baby_app_default_cheater');
        }

        return array(
            'voter' => $voter
        );
    }

    /**
     * @Route("/cheater")
     * @Template()
     */
    public function cheaterAction()
    {
        return array();
    }
}
