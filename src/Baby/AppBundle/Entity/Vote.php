<?php

namespace Baby\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Vote
 */
class Vote
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     *
     * @Assert\NotBlank(
     *     message = "Je bent je e-mail adres vergeten"
     * )
     * @Assert\Email(
     *     message = "Het e-mail adres '{{ value }}' is niet geldig.",
     *     strict = true
     * )
     */
    private $email;

    /**
     * @var string
     *
     * @Assert\NotBlank(
     *     message = "Je bent je voornaam vergeten"
     * )
     */
    private $firstname;

    /**
     * @var string
     *
     * @Assert\NotBlank(
     *     message = "Je bent je achternaam vergeten"
     * )
     */
    private $lastname;

    /**
     * @var \DateTime
     */
    private $votedAt;

    /**
     * @var string
     *
     * @Assert\NotBlank(
     *     message = "Je bent je keuze vergeten"
     * )
     */
    private $vote;

    /**
     * @var string
     */
    private $activationKey;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Vote
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     * @return Vote
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     * @return Vote
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set votedAt
     *
     * @param \DateTime $votedAt
     * @return Vote
     */
    public function setVotedAt($votedAt)
    {
        $this->votedAt = $votedAt;

        return $this;
    }

    /**
     * Get votedAt
     *
     * @return \DateTime
     */
    public function getVotedAt()
    {
        return $this->votedAt;
    }

    /**
     * Set vote
     *
     * @param string $vote
     * @return Vote
     */
    public function setVote($vote)
    {
        $this->vote = $vote;

        return $this;
    }

    /**
     * Get vote
     *
     * @return string
     */
    public function getVote()
    {
        return $this->vote;
    }

    /**
     * Set activationKey
     *
     * @param string $activationKey
     * @return Vote
     */
    public function setActivationKey($activationKey)
    {
        $this->activationKey = $activationKey;

        return $this;
    }

    /**
     * Get activationKey
     *
     * @return string
     */
    public function getActivationKey()
    {
        return $this->activationKey;
    }
}
