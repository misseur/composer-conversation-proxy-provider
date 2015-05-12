<?php

namespace ETNA\Silex\Provider\ConversationProxy;

class Conversation
{
    /* @var integer $id */
    private $id;

    /* @var string $title */
    private $title;

    /* @var string $metas */
    private $metas;

    /* @var \DateTime $created_at */
    private $created_at;

    /* @var \DateTime $updated_at */
    private $updated_at;

    /* @var \DateTime $deleted_at */
    private $deleted_at;

    /* @var array $acls */
    private $acls;

    /* @var array $messages */
    private $messages;

    /* @var array $save_actions */
    private $save_actions;

    public function __construct()
    {
        $this->acls           = [];
        $this->messages       = [];
        $this->save_actions[] = [
            "method" => "post",
            "route"  => "/conversations",
        ];
    }

    /**
     * Get id of conversation
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get title of conversation
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title of conversation
     *
     * @param string $title
     *
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get metas of conversation
     *
     * @return array
     */
    public function getMetas()
    {
        return $this->metas;
    }

    /**
     * Set metas of conversation
     *
     * @param array $metas
     *
     * @return self
     */
    public function setMetas($metas)
    {
        $this->metas = $metas;
        $this->addSaveAction([
            "method" => "put",
            "route"  => "/conversations/{$this->id}",
            "metas"  => $metas,
        ]);

        return $this;
    }

    /**
     * Get create date of conversation
     *
     * @return \DateTime|null|string
     */
    public function getCreatedAt($format = null)
    {
        if (null === $format || null === $this->created_at || is_string($this->created_at)) {
            return $this->created_at;
        }
        return $this->created_at->format($format);
    }

    /**
     * Get update date of conversation
     *
     * @return \DateTime|null|string
     */
    public function getUpdatedAt($format = null)
    {
        if (null === $format || null === $this->updated_at || is_string($this->updated_at)) {
            return $this->updated_at;
        }
        return $this->updated_at->format($format);
    }

    /**
     * Get delete date of conversation
     *
     * @return \DateTime|null|string
     */
    public function getDeletedAt($format = null)
    {
        if (null === $format || null === $this->deleted_at || is_string($this->deleted_at)) {
            return $this->deleted_at;
        }
        return $this->deleted_at->format($format);
    }

    /**
     * Add acl to conversation
     *
     * @param  array $acl
     *
     * @return self
     */
    public function addAcl(array $acl)
    {
        if (false === in_array($acl, $this->acls)) {
            $this->acls[] = $acl;
            $this->addSaveAction([
                "method" => "post",
                "route"  => "/conversations/{$this->id}/acls",
                "acl"    => $acl,
            ]);
        }

        return $this;
    }

    /**
     * Removes acl from conversation
     *
     * @param  array  $acl
     *
     * @return self
     */
    public function removeAcl(array $acl)
    {
        $index = array_search($acl, $this->acls);
        if (false !== $index) {
            $this->acls[$index]["read"]  = false;
            $this->acls[$index]["write"] = false;
            $this->addSaveAction([
                "method" => "post",
                "route"  => "/conversations/{$this->id}/acls",
                "acl"    => $this->acls[$index],
            ]);
        }

        return $this;
    }

    /**
     * Get conversation acls
     *
     * @return array
     */
    public function getAcls()
    {
        return $this->acls;
    }

    /**
     * Add message to conversation
     *
     * @param  string $message
     *
     * @return self
     */
    public function addMessage($message)
    {
        if (false === is_string($message)) {
            return $this;
        }

        $this->messages[] = ["content" => $message];
        $this->addSaveAction([
            "method"  => "post",
            "route"   => "/conversations/{$this->id}/messages",
            "content" => $message,
        ]);

        return $this;
    }

    /**
     * Get conversation messages
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Fills itself from array
     *
     * @param  array  $conversation
     *
     * @return self
     */
    public function fromArray(array $conversation)
    {
        $this->save_actions = [];
        foreach ($conversation as $field => $value) {
            if (true === property_exists($this, $field)) {
                $this->{$field} = $value;
            }
        }

        return $this;
    }

    /**
     * Serialize the conversation
     *
     * @return array
     */
    public function toArray()
    {
        return [
            "id"         => $this->getId(),
            "title"      => $this->getTitle(),
            "metas"      => $this->getMetas(),
            "acls"       => $this->getAcls(),
            "messages"   => $this->getMessages(),
            "created_at" => $this->getCreatedAt("c"),
            "updated_at" => $this->getUpdatedAt("c"),
            "deleted_at" => $this->getDeletedAt("c"),
        ];
    }

    /**
     * Add an action to execute when conversation will be saved
     *
     * @param array $save_action
     */
    private function addSaveAction(array $save_action)
    {
        $is_conversation_create = in_array(["method" => "post", "route" => "/conversations"], $this->save_actions);

        if (false === $is_conversation_create &&
            (isset($save_action["content"]) || isset($save_action["acl"]))
        ) {
            $this->save_actions[] = $save_action;
        }

        if (false === $is_conversation_create && isset($save_action["metas"])) {
            $replaced = false;
            foreach ($this->save_actions as $key => $action) {
                if (isset($action["metas"])) {
                    $this->save_actions[$key]["metas"] = $save_action["metas"];
                    $replaced = true;
                }
            }
            if (false === $replaced) {
                $this->save_actions[] = $save_action;
            }
        }
    }

    /**
     * Gets the actions to be done to save the conversation's changes
     *
     * @return array
     */
    public function getSaveActions()
    {
        return $this->save_actions;
    }
}
