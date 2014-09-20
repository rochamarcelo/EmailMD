<?php
namespace EmailMD;
/**
 * Mailbox, read and find messages
 *
 * @package EmailMD
 */
class Mailbox implements \Iterator
{
    private $_stream;

    private $_messageNumbers = array();

    private $_factory;


    /**
     * Message list
     *
     * @var array
     */
    private $_messages = array();

    /**
     * construct
     *
     * @param ImapStream $stream Imap stream
     *
     * @access public
     * @throws InvalidArgumentException when mailbox, username or password is empty
     * @return void
     */
    public function __construct(ImapStream $stream, EntityFactory $EntityFactory)
    {
        $this->_stream = $stream;
        $this->_factory = $EntityFactory;
    }

    /**
     * Filter messages, finding messags since an specific date. In format "YYYY-MM-DD
     *
     * @param DateTime $date date object
     *
     * @access public
     * @return self
     */
    public function filterSince(\DateTime $date)
    {
        $numbers = $this->_stream->search('SINCE '. $date->format('Y-m-d'));

        if ( !is_array($numbers) ) {
            $this->_messageNumbers = array();
        } else {
            $this->_messageNumbers = $numbers;
        }
        $this->rewind();
        return $this;
    }

    /**
     * Refresh this mail box
     *
     * @access public
     * @return self
     */
    public function refresh()
    {
        $count = $this->_stream->num_msg();

        if ( $count >= 1 ) {
            $this->_messageNumbers = range(1, $count);
        } else {
            $this->_messageNumbers = array();
        }
        $this->rewind();
        return $this;
    }

    /**
     * Get or fetch a message
     *
     * @param integer $number a message number
     *
     * @access public
     * @return EmailMD\Entity\Message
     */
    private function _readMessage($number)
    {
        if ( !isset($this->_messages[$number]) ) {
            $overview = $this->_stream->fetch_overview($number);
            if ( isset($overview[0]) ) {
                $overview = $overview[0];
            }
            $body = $this->_stream->body($number, 0);

            $this->_messages[$number] = $this->_factory->make($overview, $body);
        }
        return $this->_messages[$number];
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @return null
     */
    public function rewind()
    {
        return reset($this->_messageNumbers);
    }

    /**
     * Return the current message
     *
     * @return EmailMD\Entity\Message
     */
    public function current()
    {
        $number = $this->key();
        return $this->_readMessage($number);
    }

    /**
     * Return the key of the current message
     *
     * @return int
     */
    public function key()
    {
        return current($this->_messageNumbers);
    }

    /**
     * Move forward to next element
     *
     * @return null
     */
    public function next()
    {
        next($this->_messageNumbers);
    }

    /**
     * Checks if current position is valid
     *
     * @return boolean
     */
    public function valid()
    {
        return key($this->_messageNumbers) !== null;
    }
}