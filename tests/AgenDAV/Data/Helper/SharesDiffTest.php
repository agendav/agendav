<?php
namespace AgenDAV\Data\Helper;

use AgenDAV\Data\Share;

class SharesDiffTest extends \PHPUnit_Framework_TestCase
{
    public function testEmpty()
    {
        $diff = new SharesDiff([]);
        $diff->decide([]);

        $this->assertCount(0, $diff->getKeptShares(), '[] + [] != []');
        $this->assertCount(0, $diff->getMarkedForRemoval(), '[] - [] != []');
    }

    public function testAdd()
    {
        $diff = new SharesDiff([]);
        $input = $this->generateShares(4);
        $diff->decide($input);

        $this->assertCount(4, $diff->getKeptShares(), '[] + [a,b,c,d] != [a,b,c,d]');
        $this->assertCount(0, $diff->getMarkedForRemoval(), '[] - [] != []');

        $this->assertTrue(
            $this->assertAllObjectsAreEqual($diff->getKeptShares(), $input),
            '[] + [a,b,c,d] result objects do not match [a,b,c,d]'
        );
    }

    /**
     * Test what happens when we have N existing shares and we add one more
     */
    public function testAddNewShare()
    {
        $input = $this->generateShares(5);
        $existing = array_slice($input, 0, 4);
        shuffle($existing);
        $diff = new SharesDiff($existing);
        $diff->decide($input);

        $this->assertCount(5, $diff->getKeptShares(), '[a,b,c,d] + [a,b,c,d,e] != [a,b,c,d,e]');
        $this->assertCount(0, $diff->getMarkedForRemoval(), '[a,b,c,d] - [] != []');

        $this->assertTrue(
            $this->assertAllObjectsAreEqual($diff->getKeptShares(), $input),
            '[a,b,c,d] + [a,b,c,d,e] result objects do not match [a,b,c,d,e]'
        );
    }

    /**
     * Test what happens when we have N existing shares and we remove one from them
     */
    public function testRemoveOneShare()
    {
        $existing = $this->generateShares(5);
        $input = array_slice($existing, 0, 4);
        shuffle($input);
        $diff = new SharesDiff($existing);
        $diff->decide($input);

        $this->assertCount(4, $diff->getKeptShares(), '[a,b,c,d,e] - [e] != [a,b,c,d]');
        $this->assertCount(1, $diff->getMarkedForRemoval(), '[a,b,c,d,e] diff [a,b,c,d] != [e]');

        $this->assertTrue(
            $this->assertAllObjectsAreEqual($diff->getKeptShares(), $input),
            '[a,b,c,d,e] - [e] result objects do not match [a,b,c,d]'
        );

        $this->assertEquals(
            $existing[4],
            current($diff->getMarkedForRemoval()),
            'Last element was not removed'
        );
    }

    /**
     * Test what happens when we have N existing shares and we alter one of them.
     *
     * It should not remove any of the existing shares but modify the original one
     */
    public function testAlterOneShare()
    {
        $existing = $this->generateShares(5);
        foreach ($existing as $share) {
            $input[] = clone $share;
        }

        $to_be_changed = $input[4];
        $input[4]->setWritePermission(!$input[4]->isWritable());
        shuffle($input);

        $diff = new SharesDiff($existing);
        $diff->decide($input);

        $this->assertCount(5, $diff->getKeptShares(), '[a,b,c,d,e] + [a,b,c,d,e\'] != [a,b,c,d,e\']');
        $this->assertCount(0, $diff->getMarkedForRemoval(), '[a,b,c,d,e] diff [a,b,c,d,e\'] != []');

        $this->assertTrue(
            $this->assertAllObjectsAreEqual($diff->getKeptShares(), $input),
            '[a,b,c,d,e] - [e] + [e\'] result objects do not match [a,b,c,d,e\']'
        );

        $this->assertEquals(
            $to_be_changed,
            $existing[4],
            '4th element should have changed'
        );
    }


    /**
     * Generates N shares
     *
     * @param int $n Number of shares
     * @return \AgenDAV\Data\Share[]
     */
    protected function generateShares($n)
    {
        $with = '/with';
        $result = [];

        for($i=0;$i<$n;$i++) {
            $share = new Share;
            $share->setWith($with . '-' . $i);
            $share->setCalendar('/calendar');
            $share->setOwner('/me');
            $share->setWritePermission(rand(0, 1) == 1);

            $result[] = $share;
        }

        return $result;
    }

    /**
     * @return bool
     */
    protected function assertAllObjectsAreEqual(array $first, array $second)
    {
        if (count($first) != count($second)) {
            return false;
        }

        $diff = array_udiff($second, $first, function(Share $s1, Share $s2) {
            if ($s1->getWith() == $s2->getWith() && $s1->isWritable() === $s2->isWritable()) {
                return 0;
            }

            $id_1 = substr($s1->getWith(), 5); // 'with-'
            $id_2 = substr($s2->getWith(), 5); // 'with-'

            return (int)$id_1 - (int)$id_2;
        });

        if (count($diff) != 0) {
            return false;
        }

        return true;
    }
}
