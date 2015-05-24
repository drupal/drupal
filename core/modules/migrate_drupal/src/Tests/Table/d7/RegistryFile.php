<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\Table\d7\RegistryFile.
 *
 * THIS IS A GENERATED FILE. DO NOT EDIT.
 *
 * @see core/scripts/migrate-db.sh
 * @see https://www.drupal.org/sandbox/benjy/2405029
 */

namespace Drupal\migrate_drupal\Tests\Table\d7;

use Drupal\migrate_drupal\Tests\Dump\DrupalDumpBase;

/**
 * Generated file to represent the registry_file table.
 */
class RegistryFile extends DrupalDumpBase {

  public function load() {
    $this->createTable("registry_file", array(
      'primary key' => array(
        'filename',
      ),
      'fields' => array(
        'filename' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
        ),
        'hash' => array(
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '64',
        ),
      ),
    ));
    $this->database->insert("registry_file")->fields(array(
      'filename',
      'hash',
    ))
    ->values(array(
      'filename' => 'includes/actions.inc',
      'hash' => 'f36b066681463c7dfe189e0430cb1a89bf66f7e228cbb53cdfcd93987193f759',
    ))->values(array(
      'filename' => 'includes/ajax.inc',
      'hash' => 'f5d608554c6b42b976d6a97e1efffe53c657e9fbb77eabb858935bfdf4276491',
    ))->values(array(
      'filename' => 'includes/archiver.inc',
      'hash' => 'bdbb21b712a62f6b913590b609fd17cd9f3c3b77c0d21f68e71a78427ed2e3e9',
    ))->values(array(
      'filename' => 'includes/authorize.inc',
      'hash' => '6d64d8c21aa01eb12fc29918732e4df6b871ed06e5d41373cb95c197ed661d13',
    ))->values(array(
      'filename' => 'includes/batch.inc',
      'hash' => '059da9e36e1f3717f27840aae73f10dea7d6c8daf16f6520401cc1ca3b4c0388',
    ))->values(array(
      'filename' => 'includes/batch.queue.inc',
      'hash' => '554b2e92e1dad0f7fd5a19cb8dff7e109f10fbe2441a5692d076338ec908de0f',
    ))->values(array(
      'filename' => 'includes/bootstrap.inc',
      'hash' => '1433438e685f5b982c2259cd3306508c274d6065f63e3e43b6b120f1f7add621',
    ))->values(array(
      'filename' => 'includes/cache-install.inc',
      'hash' => 'e7ed123c5805703c84ad2cce9c1ca46b3ce8caeeea0d8ef39a3024a4ab95fa0e',
    ))->values(array(
      'filename' => 'includes/cache.inc',
      'hash' => 'd01e10e4c18010b6908026f3d71b72717e3272cfb91a528490eba7f339f8dd1b',
    ))->values(array(
      'filename' => 'includes/common.inc',
      'hash' => '91bf90492c571dba1f6ef7db54a96d360579f933d0f3637b5aa33dff1eeda56a',
    ))->values(array(
      'filename' => 'includes/database/database.inc',
      'hash' => '24afaff6e1026bfe315205212cba72951240a16154250e405c4c64724e6e07cc',
    ))->values(array(
      'filename' => 'includes/database/log.inc',
      'hash' => '9feb5a17ae2fabcf26a96d2a634ba73da501f7bcfc3599a693d916a6971d00d1',
    ))->values(array(
      'filename' => 'includes/database/mysql/database.inc',
      'hash' => 'd62a2d8ca103cb3b085e7f8b894a7db14c02f20d0b1ed0bd32f6534a45b4527f',
    ))->values(array(
      'filename' => 'includes/database/mysql/install.inc',
      'hash' => '6ae316941f771732fbbabed7e1d6b4cbb41b1f429dd097d04b3345aa15e461a0',
    ))->values(array(
      'filename' => 'includes/database/mysql/query.inc',
      'hash' => '0212a871646c223bf77aa26b945c77a8974855373967b5fb9fdc09f8a1de88a6',
    ))->values(array(
      'filename' => 'includes/database/mysql/schema.inc',
      'hash' => '6f43ac87508f868fe38ee09994fc18d69915bada0237f8ac3b717cafe8f22c6b',
    ))->values(array(
      'filename' => 'includes/database/pgsql/database.inc',
      'hash' => 'd737f95947d78eb801e8ec8ca8b01e72d2e305924efce8abca0a98c1b5264cff',
    ))->values(array(
      'filename' => 'includes/database/pgsql/install.inc',
      'hash' => '585b80c5bbd6f134bff60d06397f15154657a577d4da8d1b181858905f09dea5',
    ))->values(array(
      'filename' => 'includes/database/pgsql/query.inc',
      'hash' => '0df57377686c921e722a10b49d5e433b131176c8059a4ace4680964206fc14b4',
    ))->values(array(
      'filename' => 'includes/database/pgsql/schema.inc',
      'hash' => '1588daadfa53506aa1f5d94572162a45a46dc3ceabdd0e2f224532ded6508403',
    ))->values(array(
      'filename' => 'includes/database/pgsql/select.inc',
      'hash' => 'fd4bba7887c1dc6abc8f080fc3a76c01d92ea085434e355dc1ecb50d8743c22d',
    ))->values(array(
      'filename' => 'includes/database/prefetch.inc',
      'hash' => 'b5b207a66a69ecb52ee4f4459af16a7b5eabedc87254245f37cc33bebb61c0fb',
    ))->values(array(
      'filename' => 'includes/database/query.inc',
      'hash' => '9171653e9710c6c0d20cff865fdead5a580367137ad4cdf81059ecc2eea61c74',
    ))->values(array(
      'filename' => 'includes/database/schema.inc',
      'hash' => 'a98b69d33975e75f7d99cb85b20c36b7fc10e35a588e07b20c1b37500f5876ca',
    ))->values(array(
      'filename' => 'includes/database/select.inc',
      'hash' => '5e9cdc383564ba86cb9dcad0046990ce15415a3000e4f617d6e0f30a205b852c',
    ))->values(array(
      'filename' => 'includes/database/sqlite/database.inc',
      'hash' => '4281c6e80932560ecbeb07d1757efd133e8699a6fccf58c27a55df0f71794622',
    ))->values(array(
      'filename' => 'includes/database/sqlite/install.inc',
      'hash' => '381f3db8c59837d961978ba3097bb6443534ed1659fd713aa563963fa0c42cc5',
    ))->values(array(
      'filename' => 'includes/database/sqlite/query.inc',
      'hash' => 'f33ab1b6350736a231a4f3f93012d3aac4431ac4e5510fb3a015a5aa6cab8303',
    ))->values(array(
      'filename' => 'includes/database/sqlite/schema.inc',
      'hash' => 'cd829700205a8574f8b9d88cd1eaf909519c64754c6f84d6c62b5d21f5886f8d',
    ))->values(array(
      'filename' => 'includes/database/sqlite/select.inc',
      'hash' => '8d1c426dbd337733c206cce9f59a172546c6ed856d8ef3f1c7bef05a16f7bf68',
    ))->values(array(
      'filename' => 'includes/date.inc',
      'hash' => '18c047be64f201e16d189f1cc47ed9dcf0a145151b1ee187e90511b24e5d2b36',
    ))->values(array(
      'filename' => 'includes/entity.inc',
      'hash' => '3080fe3c30991a48f1f314a60d02e841d263a8f222337e5bde3be61afe41ee7a',
    ))->values(array(
      'filename' => 'includes/errors.inc',
      'hash' => '72cc29840b24830df98a5628286b4d82738f2abbb78e69b4980310ff12062668',
    ))->values(array(
      'filename' => 'includes/file.inc',
      'hash' => '5ee60833470d5e8d5f2c6c8e7b978ec2e1f3cbf291cb611db1ca560dea98d888',
    ))->values(array(
      'filename' => 'includes/file.mimetypes.inc',
      'hash' => '33266e837f4ce076378e7e8cef6c5af46446226ca4259f83e13f605856a7f147',
    ))->values(array(
      'filename' => 'includes/filetransfer/filetransfer.inc',
      'hash' => 'fdea8ae48345ec91885ac48a9bc53daf87616271472bb7c29b7e3ce219b22034',
    ))->values(array(
      'filename' => 'includes/filetransfer/ftp.inc',
      'hash' => '51eb119b8e1221d598ffa6cc46c8a322aa77b49a3d8879f7fb38b7221cf7e06d',
    ))->values(array(
      'filename' => 'includes/filetransfer/local.inc',
      'hash' => '7cbfdb46abbdf539640db27e66fb30e5265128f31002bd0dfc3af16ae01a9492',
    ))->values(array(
      'filename' => 'includes/filetransfer/ssh.inc',
      'hash' => '92f1232158cb32ab04cbc93ae38ad3af04796e18f66910a9bc5ca8e437f06891',
    ))->values(array(
      'filename' => 'includes/form.inc',
      'hash' => 'ead5e56f116ba31898d1b73f1dfc19ea57a9a528f87c9497fd60ad5caedfee2b',
    ))->values(array(
      'filename' => 'includes/graph.inc',
      'hash' => '8e0e313a8bb33488f371df11fc1b58d7cf80099b886cd1003871e2c896d1b536',
    ))->values(array(
      'filename' => 'includes/image.inc',
      'hash' => 'bcdc7e1599c02227502b9d0fe36eeb2b529b130a392bc709eb737647bd361826',
    ))->values(array(
      'filename' => 'includes/install.core.inc',
      'hash' => 'a0585c85002e6f3d702dc505584f48b55bc13e24bee749bfe5b718fbce4847e1',
    ))->values(array(
      'filename' => 'includes/install.inc',
      'hash' => '480c3cfd065d3ec00f4465e1b0a0d55d6a8927e78fd6774001c30163a5c648e3',
    ))->values(array(
      'filename' => 'includes/iso.inc',
      'hash' => '0ce4c225edcfa9f037703bc7dd09d4e268a69bcc90e55da0a3f04c502bd2f349',
    ))->values(array(
      'filename' => 'includes/json-encode.inc',
      'hash' => '02a822a652d00151f79db9aa9e171c310b69b93a12f549bc2ce00533a8efa14e',
    ))->values(array(
      'filename' => 'includes/language.inc',
      'hash' => '4dd521af07e0ca7bf97ff145f4bd3a218acf0d8b94964e72f11212bb8af8d66e',
    ))->values(array(
      'filename' => 'includes/locale.inc',
      'hash' => 'b250f375b93ffe3749f946e0ad475065c914af23e388d68e5c5df161590f086a',
    ))->values(array(
      'filename' => 'includes/lock.inc',
      'hash' => 'a181c8bd4f88d292a0a73b9f1fbd727e3314f66ec3631f288e6b9a54ba2b70fa',
    ))->values(array(
      'filename' => 'includes/mail.inc',
      'hash' => 'd9fb2b99025745cbb73ebcfc7ac12df100508b9273ce35c433deacf12dd6a13a',
    ))->values(array(
      'filename' => 'includes/menu.inc',
      'hash' => 'c9ff3c7db04b7e01d0d19b5e47d9fb209799f2ae6584167235b957d22542e526',
    ))->values(array(
      'filename' => 'includes/module.inc',
      'hash' => 'ca3f2e6129181bbbc67e5e6058a882047f2152174ec8e95c0ea99ce610ace4d7',
    ))->values(array(
      'filename' => 'includes/pager.inc',
      'hash' => '6f9494b85c07a2cc3be4e54aff2d2757485238c476a7da084d25bde1d88be6d8',
    ))->values(array(
      'filename' => 'includes/password.inc',
      'hash' => 'fd9a1c94fe5a0fa7c7049a2435c7280b1d666b2074595010e3c492dd15712775',
    ))->values(array(
      'filename' => 'includes/path.inc',
      'hash' => '74bf05f3c68b0218730abf3e539fcf08b271959c8f4611940d05124f34a6a66f',
    ))->values(array(
      'filename' => 'includes/registry.inc',
      'hash' => 'c225de772f86eebd21b0b52fa8fcc6671e05fa2374cedb3164f7397f27d3c88d',
    ))->values(array(
      'filename' => 'includes/session.inc',
      'hash' => '7548621ae4c273179a76eba41aa58b740100613bc015ad388a5c30132b61e34b',
    ))->values(array(
      'filename' => 'includes/stream_wrappers.inc',
      'hash' => '4f1feb774a8dbc04ca382fa052f59e58039c7261625f3df29987d6b31f08d92d',
    ))->values(array(
      'filename' => 'includes/tablesort.inc',
      'hash' => '2d88768a544829595dd6cda2a5eb008bedb730f36bba6dfe005d9ddd999d5c0f',
    ))->values(array(
      'filename' => 'includes/theme.inc',
      'hash' => '0465fb4ed937123c4bed4a4463601055f9b8fc39ca7787d9952b4f4e300db2b3',
    ))->values(array(
      'filename' => 'includes/theme.maintenance.inc',
      'hash' => '39f068b3eee4d10a90d6aa3c86db587b6d25844c2919d418d34d133cfe330f5a',
    ))->values(array(
      'filename' => 'includes/token.inc',
      'hash' => '5e7898cd78689e2c291ed3cd8f41c032075656896f1db57e49217aac19ae0428',
    ))->values(array(
      'filename' => 'includes/unicode.entities.inc',
      'hash' => '2b858138596d961fbaa4c6e3986e409921df7f76b6ee1b109c4af5970f1e0f54',
    ))->values(array(
      'filename' => 'includes/unicode.inc',
      'hash' => 'e18772dafe0f80eb139fcfc582fef1704ba9f730647057d4f4841d6a6e4066ca',
    ))->values(array(
      'filename' => 'includes/update.inc',
      'hash' => '177ce24362efc7f28b384c90a09c3e485396bbd18c3721d4b21e57dd1733bd92',
    ))->values(array(
      'filename' => 'includes/updater.inc',
      'hash' => 'd2da0e74ed86e93c209f16069f3d32e1a134ceb6c06a0044f78e841a1b54e380',
    ))->values(array(
      'filename' => 'includes/utility.inc',
      'hash' => '3458fd2b55ab004dd0cc529b8e58af12916e8bd36653b072bdd820b26b907ed5',
    ))->values(array(
      'filename' => 'includes/xmlrpc.inc',
      'hash' => 'ea24176ec445c440ba0c825fc7b04a31b440288df8ef02081560dc418e34e659',
    ))->values(array(
      'filename' => 'includes/xmlrpcs.inc',
      'hash' => '741aa8d6fcc6c45a9409064f52351f7999b7c702d73def8da44de2567946598a',
    ))->values(array(
      'filename' => 'modules/aggregator/aggregator.test',
      'hash' => '1288945ead1e0b250cb0f2d8bc5486ab1c67295b78b5f1ba0f77ade7bf1243b4',
    ))->values(array(
      'filename' => 'modules/block/block.test',
      'hash' => 'df1b364688b46345523dfcb95c0c48352d6a4edbc66597890d29b9b0d7866e86',
    ))->values(array(
      'filename' => 'modules/blog/blog.test',
      'hash' => 'f7534b972951c05d34bd832d3e06176b372fff6f4999c428f789fdd7703ed2e2',
    ))->values(array(
      'filename' => 'modules/book/book.test',
      'hash' => 'a75a4ec12f930d85adbf7c46d6a1a4ed1356657466874f21e9cc931b6cd41aa0',
    ))->values(array(
      'filename' => 'modules/color/color.test',
      'hash' => '013806279bd47ceb2f82ca854b57f880ba21058f7a2592c422afae881a7f5d15',
    ))->values(array(
      'filename' => 'modules/comment/comment.module',
      'hash' => '5a81f5e4b3a35973b3d39ccb9efaee7a8f8cf4ac43e9353e87f2d17a3bed4747',
    ))->values(array(
      'filename' => 'modules/comment/comment.test',
      'hash' => '083d47035d3e64d1f6f9f1e12bc13d056511019a9de84183088e58a359ea58b9',
    ))->values(array(
      'filename' => 'modules/contact/contact.test',
      'hash' => 'd49eedd71859fbb6ffa26b87226f640db56694c8f43c863c83d920cf3632f9ad',
    ))->values(array(
      'filename' => 'modules/contextual/contextual.test',
      'hash' => '023dafa199bd325ecc55a17b2a3db46ac0a31e23059f701f789f3bc42427ba0b',
    ))->values(array(
      'filename' => 'modules/dashboard/dashboard.test',
      'hash' => '125df00fc6deb985dc554aa7807a48e60a68dbbddbad9ec2c4718da724f0e683',
    ))->values(array(
      'filename' => 'modules/dblog/dblog.test',
      'hash' => '11fbb8522b1c9dc7c85edba3aed7308a8891f26fc7292008822bea1b54722912',
    ))->values(array(
      'filename' => 'modules/field/field.attach.inc',
      'hash' => '2df4687b5ec078c4893dc1fea514f67524fd5293de717b9e05caf977e5ae2327',
    ))->values(array(
      'filename' => 'modules/field/field.info.class.inc',
      'hash' => 'a6f2f418552dba0e03f57ee812a6f0f63bbfe4bf81fe805d51ecec47ef84b845',
    ))->values(array(
      'filename' => 'modules/field/field.module',
      'hash' => '2ec1a3ec060504467c3065426a5a1eca8e2c894cb4d4480616bca60fe4b2faf2',
    ))->values(array(
      'filename' => 'modules/field/modules/field_sql_storage/field_sql_storage.test',
      'hash' => '24b4d2596016ff86071ff3f00d63ff854e847dc58ab64a0afc539bdc1f682ac5',
    ))->values(array(
      'filename' => 'modules/field/modules/list/tests/list.test',
      'hash' => '97e55bd49f6f4b0562d04aa3773b5ab9b35063aee05c8c7231780cdcf9c97714',
    ))->values(array(
      'filename' => 'modules/field/modules/number/number.test',
      'hash' => '9ccf835bbf80ff31b121286f6fbcf59cc42b622a51ab56b22362b2f55c656e18',
    ))->values(array(
      'filename' => 'modules/field/modules/options/options.test',
      'hash' => 'c71441020206b1587dece7296cca306a9f0fbd6e8f04dae272efc15ed3a38383',
    ))->values(array(
      'filename' => 'modules/field/modules/text/text.test',
      'hash' => 'a1e5cb0fa8c0651c68d560d9bb7781463a84200f701b00b6e797a9ca792a7e42',
    ))->values(array(
      'filename' => 'modules/field/tests/field.test',
      'hash' => '0c9c6f9396ab8e0685951f4e90f298629c31d2f7970e5b288e674bc146fefa90',
    ))->values(array(
      'filename' => 'modules/field_ui/field_ui.test',
      'hash' => 'da42e28d6f32d447b4a6e5b463a2f7d87d6ce32f149de04a98fa8e3f286c9f68',
    ))->values(array(
      'filename' => 'modules/file/tests/file.test',
      'hash' => '5cb7a7a6cc14a6d4269bf4d406a304f77052be7691e0ec9b8e7c5262316d7539',
    ))->values(array(
      'filename' => 'modules/filter/filter.test',
      'hash' => '13330238c7b8d280ff2dd8cfee1c001d5a994ad45e3c9b9c5fdcd963c6080926',
    ))->values(array(
      'filename' => 'modules/forum/forum.test',
      'hash' => 'd282b29d6312d63183e003ba036d7645a946e828c94448592f930d80fceb42d6',
    ))->values(array(
      'filename' => 'modules/help/help.test',
      'hash' => 'bc934de8c71bd9874a05ccb5e8f927f4c227b3b2397d739e8504c8fd6ae5a83c',
    ))->values(array(
      'filename' => 'modules/image/image.test',
      'hash' => 'd6ea03d1e3df0e150ed3500b9896984e5c3cd5f28248f2aebecce5b9926eb23b',
    ))->values(array(
      'filename' => 'modules/locale/locale.test',
      'hash' => '59fe99927c790699e0d5a7047df7c05eb9ba3ef4c1363a929e7a65115da24f1a',
    ))->values(array(
      'filename' => 'modules/menu/menu.test',
      'hash' => 'cd187c84aa97dcc228d8a1556ea10640c62f86083034533b6ac6830be610ca2a',
    ))->values(array(
      'filename' => 'modules/node/node.module',
      'hash' => '3489bbd7e909b21c54a1bd5e4d4daeafb9bebc6606e48fe1d5e7a6ed935a1a3e',
    ))->values(array(
      'filename' => 'modules/node/node.test',
      'hash' => 'e2e485fde00796305fd6926c8b4e9c4e1919020a3ec00819aa5cc1d2b3ebcc5c',
    ))->values(array(
      'filename' => 'modules/openid/openid.test',
      'hash' => '16661e3a940c69cfdabf6ba28ad8f99ae3854dcf43bbac0e823d2f1d32f7cb09',
    ))->values(array(
      'filename' => 'modules/path/path.test',
      'hash' => '2004183b2c7c86028bf78c519c6a7afc4397a8267874462b0c2b49b0f8c20322',
    ))->values(array(
      'filename' => 'modules/php/php.test',
      'hash' => 'd234f9c1ab18a05834a3cb6dc532fb4c259aa25612551f953ba6e3bb714657b8',
    ))->values(array(
      'filename' => 'modules/poll/poll.test',
      'hash' => 'cc8486dc337471d13014954e1c1e4e5ad4956e4a0cbd395adbd064f8e5849c72',
    ))->values(array(
      'filename' => 'modules/profile/profile.test',
      'hash' => '6dcc39fcc5b07b56c2aab3eebd8432ffd33ee1d33fe1574ec9e665f8acb15330',
    ))->values(array(
      'filename' => 'modules/rdf/rdf.test',
      'hash' => '9849d2b717119aa6b5f1496929e7ac7c9c0a6e98486b66f3876bda0a8c165525',
    ))->values(array(
      'filename' => 'modules/search/search.extender.inc',
      'hash' => 'c40f6569769ff581dbe11d29935c611320178f9a076977423e1d93e7d98013fa',
    ))->values(array(
      'filename' => 'modules/search/search.test',
      'hash' => '71ffda1d5c81823aa6f557ca35ba451df2f684856174e25e917f8bf4f0c72453',
    ))->values(array(
      'filename' => 'modules/shortcut/shortcut.test',
      'hash' => '0d78280d4d0a05aa772218e45911552e39611ca9c258b9dd436307914ac3f254',
    ))->values(array(
      'filename' => 'modules/simpletest/drupal_web_test_case.php',
      'hash' => '2eb60780f843a6713fc2856f601b811ccd26b38f2b5afd845c113f093d97a138',
    ))->values(array(
      'filename' => 'modules/simpletest/simpletest.test',
      'hash' => '8af29b4eaa4942baf69d3210220d855743411ad2b575c9fb12e1b220aa45e3e0',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/actions.test',
      'hash' => '4e61dcbff514581321b47b8b2402cfb387d859b1a9944cb70bf9f33977dd5220',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/ajax.test',
      'hash' => '692a582ca0164c4374c7567d4c23b2f2c6638f7f88fd16d3a709c7af5e8744de',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/batch.test',
      'hash' => '665a621f4d5f819295ca7c53158d586ed98c42d8a8e6db1e67fb332032ec07d5',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/bootstrap.test',
      'hash' => '5c6a25807ace61032eb7c5f59b4d57dd426bdd58490629ca37f70954dce33a32',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/cache.test',
      'hash' => '2ff9a42287a6419acba6589cd887e9c0d765c1c201865799abe03ee6f3234dfb',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/common.test',
      'hash' => 'fd42e79d21a6807b889f70ec2feaa6034cbe98d947795cd2c0e11c592ada19aa',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/database_test.test',
      'hash' => '681e7c84278793f0b0146c89e2e78a3d3705193faab5b98bc88d4b43539a2584',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/entity_crud.test',
      'hash' => '0db2e08cb15ef287ed622fa56cee85e6a61b6e7a8547c77531a80a9ec1379d87',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/entity_crud_hook_test.test',
      'hash' => '5f3f083a018c1c0e78c8532cfc87b95d3c2b1740577a2f0eab8bc75e1db069b4',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/entity_query.test',
      'hash' => '8b107f796e9febb8080b153d3c9b969cea5bbb3cd4ee410c8f612bf7bdbb0a63',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/error.test',
      'hash' => 'df8360738a4b3c946209a560ae83065728ae1aa56744cd8aaee398325a7cda60',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/file.test',
      'hash' => '403e9020898f051c467fe54d72bc0bc0aefb784aabc90849dcf765f09694ead1',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/filetransfer.test',
      'hash' => 'a5ae7e24c43f994968d059c93d56be0dfd580699e2cb884afb074b9ae5895fd9',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/form.test',
      'hash' => 'b9af9565dbb10327c07365b7cc8c1da394085d1e1fc92d4f7e79fa1d17978a4f',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/graph.test',
      'hash' => '3038b97305b54f859a78356c184feeb773056e6c54b9ad511cc4c487ea3355c2',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/image.test',
      'hash' => '0c0331b7239519aa2ce6cafe4bff8ee078875222531e5bbe1d60e131baaa0eb0',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/lock.test',
      'hash' => '0d63de7e57c405dae03a6c04e13392c59c8dc19a843e0818cc43212af2e26242',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/mail.test',
      'hash' => '9f772652385048639264f64147eab2675e9e76be2258e70bbefc2f6f753d047f',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/menu.test',
      'hash' => 'b7602b23403271fd404646cd5f4970ce278eb3c014ed30676f1ba680cfd749a1',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/module.test',
      'hash' => '35cf0c46b1129a45229fc7b03f7229a99ded19d57dcd751a3a8858d7422ae84e',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/pager.test',
      'hash' => '9586bc07f5ed2791ae69e8cacf1a257ffe85dde3be7769ce6e84435a001bc296',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/password.test',
      'hash' => 'fadb23077d9364d0dba4fa7462d31f2ad842d840ad173f685cf6944aa679c9a7',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/path.test',
      'hash' => '814b32c225e1a73f225b52c0e5a9579a754dd9f597cb71189fa0b62c5ce821ad',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/registry.test',
      'hash' => 'eadaa4f04ffbe49656ee9c8d477a4855de12f5f7fd6923894ab6565b86fde28f',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/schema.test',
      'hash' => '14a7975e040ae8d3a7c8bb82c9e26dabe78978f6371dec22ae8c81b71cf3e4bb',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/session.test',
      'hash' => '2619d36051b1cfa9ff53b1960b784a998943167018952e4e11832f2f0324d19c',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/tablesort.test',
      'hash' => '0c0e011775ffc0e8f2d9c6f1284de28ad849ffa88df6e48677ed1c395c2267d1',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/theme.test',
      'hash' => '4843ff803a403ceff29ebf6c59ae9175cb0237b91d52305a52501610078ce1c4',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/unicode.test',
      'hash' => '91f0f16bbdb987035b562f4621bea1522aa74851e7c107663ae17d11b2ac0959',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/update.test',
      'hash' => '49f64b9b84521f9f8eaebb9610f5cc3378d0665683032320a36abda12d16be43',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/upgrade/update.aggregator.test',
      'hash' => 'a2b6a574993591e93dacbd303a300b852775a3beea1343fb1f11578a8cdd26e1',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/upgrade/update.field.test',
      'hash' => 'e8a443db8d58d743cf06957ff949370dde65b0ad35837368fd89a95ea6594d52',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/upgrade/update.trigger.test',
      'hash' => '421b8986a71c8cf30c442cd9f1736ae7ce8838214a1b6e9eae30c9c5c108acd3',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/upgrade/update.user.test',
      'hash' => 'b21ec55d94d3baec7ce807c7972fb3b348deba70a53bfb78a66553c66ede63af',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/upgrade/upgrade.comment.test',
      'hash' => 'a20a8b44b46a6bc1cc0f0a18e67a12933d0b101d463bcdc21212e5f35d93c379',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/upgrade/upgrade.filter.test',
      'hash' => '0485b6d466476a85e7591eb4bdaf303b1b75a871038f1d5669a3f6d4cd81ecd0',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/upgrade/upgrade.forum.test',
      'hash' => '6330fe5d85a81d7d5686da5a40cf18b275bef4c5819afb334f8fc0b043532bb4',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/upgrade/upgrade.locale.test',
      'hash' => 'ec2d285222dd85022a16daed2b3a3e951dba97ab4de9fd46d89d2064f5db7595',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/upgrade/upgrade.menu.test',
      'hash' => 'fa6e46dcb1028e6c3faad86c50d3d9296d7a4095115ffb8d39b627dbd31996d7',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/upgrade/upgrade.node.test',
      'hash' => 'f16f1ae5b5b3584e4d1d119473a962112fb28796efb5283aaa8df2db0ddec364',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/upgrade/upgrade.taxonomy.test',
      'hash' => '805528f81162014479d94e70bcaf234f818a1898d057e05d08b148a9120743b9',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/upgrade/upgrade.test',
      'hash' => '877364dd82e5e1ed28e1ee93c48fbec083237012f44038a8fef39d9c6cb8b4f9',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/upgrade/upgrade.translatable.test',
      'hash' => '7a53241c9df9c671fb1da2789fe32c0e0425267f1647293a89adadbfa49bc4ac',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/upgrade/upgrade.trigger.test',
      'hash' => '4ba820349ef89f6eaa73f429c053e09f937d36808149a00563efa5b551e8669d',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/upgrade/upgrade.upload.test',
      'hash' => 'a825081089f33a77a797fa6482e923a17fd90f2696267711a4c0d8a0792b9389',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/upgrade/upgrade.user.test',
      'hash' => '9c11a51f2bd262f957e6c609ec84b26ac9d1fa00feeb0078b7a12beb450daab3',
    ))->values(array(
      'filename' => 'modules/simpletest/tests/xmlrpc.test',
      'hash' => '1d9b1fe51d31722473478087d50ed09b180748047205cea936db49a74ade82e7',
    ))->values(array(
      'filename' => 'modules/statistics/statistics.test',
      'hash' => '3fc1617038ab69ce32a666def03cac5f80532d2e71bad811b4e8b0f75250736c',
    ))->values(array(
      'filename' => 'modules/syslog/syslog.test',
      'hash' => 'ad873b3d499ebad748784ae88df3496f39de1b9bbfd98c3193ef1ea70c6376ae',
    ))->values(array(
      'filename' => 'modules/system/system.archiver.inc',
      'hash' => 'faa849f3e646a910ab82fd6c8bbf0a4e6b8c60725d7ba81ec0556bd716616cd1',
    ))->values(array(
      'filename' => 'modules/system/system.mail.inc',
      'hash' => 'd31e1769f5defbe5f27dc68f641ab80fb8d3de92f6e895f4c654ec05fc7e5f0f',
    ))->values(array(
      'filename' => 'modules/system/system.queue.inc',
      'hash' => 'ef00fd41ca86de386fa134d5bc1d816f9af550cf0e1334a5c0ade3119688ca3c',
    ))->values(array(
      'filename' => 'modules/system/system.tar.inc',
      'hash' => '8a31d91f7b3cd7eac25b3fa46e1ed9a8527c39718ba76c3f8c0bbbeaa3aa4086',
    ))->values(array(
      'filename' => 'modules/system/system.test',
      'hash' => 'ad3c68f2cacfe6a99c065edc9aca05a22bdbc74ff6158e9918255b4633134ab4',
    ))->values(array(
      'filename' => 'modules/system/system.updater.inc',
      'hash' => '338cf14cb691ba16ee551b3b9e0fa4f579a2f25c964130658236726d17563b6a',
    ))->values(array(
      'filename' => 'modules/taxonomy/taxonomy.module',
      'hash' => '45d6d5652a464318f3eccf8bad6220cc5784e7ffdb0c7b732bf4d540e1effe83',
    ))->values(array(
      'filename' => 'modules/taxonomy/taxonomy.test',
      'hash' => '8525035816906e327ad48bd48bb071597f4c58368a692bcec401299a86699e6e',
    ))->values(array(
      'filename' => 'modules/tracker/tracker.test',
      'hash' => 'bea7303dfe934afeb271506da43bcf24a51d7d5546181796d7f9f70b6283ed67',
    ))->values(array(
      'filename' => 'modules/translation/translation.test',
      'hash' => 'c2ad71934a9a2139cdf8213df35f4c91dcc0e643fabb883c38e3ffbdd313d608',
    ))->values(array(
      'filename' => 'modules/trigger/trigger.test',
      'hash' => '662f1a55e62832d7d5258965ca70ebe9d36ce8ae34e05fda2a2f9dc72418978b',
    ))->values(array(
      'filename' => 'modules/update/update.test',
      'hash' => '1ea3e22bd4d47afb8b2799057cdbdfbb57ce09013d9d5f2de7e61ef9c2ebc72d',
    ))->values(array(
      'filename' => 'modules/user/user.module',
      'hash' => 'b658c75c17b263a0aa6be903429c14e0fb1c308dd4e9024e369b7e7feb2b5dce',
    ))->values(array(
      'filename' => 'modules/user/user.test',
      'hash' => 'd27160f1fd04cfb497ff080c7266fcebcd310d2224cfc6aef70035b275d65573',
    ))->values(array(
      'filename' => 'sites/all/modules/date/date.migrate.inc',
      'hash' => '6e44d2f6c8ae81a42dc545951663f13e22c914efcaaa9c7c7d7da77ee06d4ecd',
    ))->values(array(
      'filename' => 'sites/all/modules/date/date_api/date_api.module',
      'hash' => '107f1668a3a75f9b18e834f8494fc52d8ccf0d6b231c98bd10b9313cfbe18776',
    ))->values(array(
      'filename' => 'sites/all/modules/date/date_api/date_api_sql.inc',
      'hash' => '89e92bdb4eb9b348a57d70ddb933c86e81f6e7a7047cd9e77c83a454c00c5a11',
    ))->values(array(
      'filename' => 'sites/all/modules/date/date_repeat/tests/date_repeat.test',
      'hash' => '3702268fa89aa7ed9bcae025f0fb21bd67f90e89d53122049854de60a5316d4a',
    ))->values(array(
      'filename' => 'sites/all/modules/date/date_repeat/tests/date_repeat_form.test',
      'hash' => '2ec4e5d57d5b9f1adf81505d40890c63dc684f2d0f00669b9c8c12518eb3bf4a',
    ))->values(array(
      'filename' => 'sites/all/modules/date/date_tools/tests/date_tools.test',
      'hash' => 'bdb9b310295207ce2284b23556810296e1477b9604e98a3d0fb21aba7da04394',
    ))->values(array(
      'filename' => 'sites/all/modules/date/tests/date.test',
      'hash' => '6cb38e9ed60bfdfa268051b47fcad699f1c8104accc7286abafbeddbbc9d143c',
    ))->values(array(
      'filename' => 'sites/all/modules/date/tests/date_api.test',
      'hash' => 'ebaf0427701be69bd3dc8a766528d7347a87ee15c2f69b240188aa5ab1182cbd',
    ))->values(array(
      'filename' => 'sites/all/modules/date/tests/date_field.test',
      'hash' => '785a6cf0afcd4619a58263487e27e130ae0724f532018a5a127f24be1dbb4871',
    ))->values(array(
      'filename' => 'sites/all/modules/date/tests/date_migrate.test',
      'hash' => 'a43f448732d474c5136d89ff560b7e14c1ff5622f9c35a1998aa0570cd0c536c',
    ))->values(array(
      'filename' => 'sites/all/modules/date/tests/date_timezone.test',
      'hash' => '760c3e761d122bafd2fab2056362f33424416a1407e7a1423df42acd03f84495',
    ))->values(array(
      'filename' => 'sites/all/modules/date/tests/date_validation.test',
      'hash' => '2e4d27c29192c9d55eb27b985d7e9838702c4324d36ed6e3a85999e9f25ada99',
    ))->values(array(
      'filename' => 'sites/all/modules/email/email.migrate.inc',
      'hash' => 'bf3859ca39a3e5570e4ac862858f066668caab33841d65bdfa229c8445e12d5a',
    ))->values(array(
      'filename' => 'sites/all/modules/link/link.migrate.inc',
      'hash' => '0a17ff0daa79813174fff92e9db787e75e710fe757b6924eec193c66fe13f3df',
    ))->values(array(
      'filename' => 'sites/all/modules/link/link.module',
      'hash' => '3fdf23f9f409b80df5eee57207a5045c566422cef14128306835f7b1b03a5e66',
    ))->values(array(
      'filename' => 'sites/all/modules/link/tests/link.attribute.test',
      'hash' => '8c21045dbcf346edf8dc70c157d02074dd87372d4f60e7f5a4ae11683cd79399',
    ))->values(array(
      'filename' => 'sites/all/modules/link/tests/link.crud.test',
      'hash' => 'de19e2c5e8c6cb02f25d7051bdd1db77852379ac59ce8185c25b4bf927478f22',
    ))->values(array(
      'filename' => 'sites/all/modules/link/tests/link.crud_browser.test',
      'hash' => '07794771164033e14a635625ad08fdbcdc4dafa154db2ec0bd9a95ca30202eb5',
    ))->values(array(
      'filename' => 'sites/all/modules/link/tests/link.test',
      'hash' => 'fe5d8cd577fbfc07929e7216e115530a28777ace9b7135fbc853a3a78d550456',
    ))->values(array(
      'filename' => 'sites/all/modules/link/tests/link.token.test',
      'hash' => '930af3b64ccabd58a14c8f596ee4b908596b4bee64174bfa34d55fbab7173da4',
    ))->values(array(
      'filename' => 'sites/all/modules/link/tests/link.validate.test',
      'hash' => '4fb3d9124767f43332f2e40bcca3aba98575dd1f6a286adfc8831607823986c5',
    ))->values(array(
      'filename' => 'sites/all/modules/link/views/link_views_handler_argument_target.inc',
      'hash' => 'd77c23c6b085382c63e7dbce3d95afc9756517edcdc4cd455892d8333520d4c9',
    ))->values(array(
      'filename' => 'sites/all/modules/link/views/link_views_handler_filter_protocol.inc',
      'hash' => 'e10a0d2de73bfa9a56fadbac023c6ac16022ced40c0444ab6ffed1b5d7ea7358',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/phone.migrate.inc',
      'hash' => 'd7422e56ab02e4b55b2fdb9ea185a876fd6164446b0c4f66b5c0e70071d7e708',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.au.test',
      'hash' => '0b920ca34f5255c6d49b0138b9bd58fa9e430fa11e33e93ab3cd248c2b4ad0fe',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.be.test',
      'hash' => '311af0608d86bb10a02e393a2d9e541a409b081088f353eacee6ceb864130a98',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.br.test',
      'hash' => '93bad68cd3e4560cc05914aeaee8bb9056591b46917d7295f9a3d4feca89e739',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.ca.test',
      'hash' => '6f425a856adb70fb250fc8823bc010afab781839695c9ecb5f7be38bf7710348',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.ch.test',
      'hash' => 'ab8fa50c459d6e11d773c597320859d07bfdbf5ab7715bd505cff025980b7070',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.cl.test',
      'hash' => '2cc581962d4367c476b2010f10de7acb3bc9ab52177bb52694e28a88d54d48a1',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.cn.test',
      'hash' => 'e36269835b0d5673684887cf33831a254e7b2110b98dc35a60102246901c244d',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.cr.test',
      'hash' => '04c3aab79d2c75104d0ce9a9a84f84443303289bafce79b43f06d90cbc4c78b0',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.cs.test',
      'hash' => '3e90d66bedebd4c66b46526fb4877340b6b0c2d4bcd7557e477b5b9c52727d9a',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.eg.test',
      'hash' => 'f0568e235c65fc625cc2eca359c0062771df5755460cb6003ae8c5197aca67f9',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.es.test',
      'hash' => '8a8bfa8827e42a79d89720cec22c9246ff463cc8bae89f78394b61fbd676614e',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.fr.test',
      'hash' => '76dc4de9eb4547e3a27298a93fb9949f5d354b3464ec268df9da496783093115',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.hu.test',
      'hash' => '57aee8805078b0281c4722e80d721fbcc6dec739b7258bc9843139fd2652471f',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.il.test',
      'hash' => '5dba7aa580c087df4d0d9e8a9d8a00a0203a3217cce2e14a26fc18371964f5d5',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.int.test',
      'hash' => '99b33e5ec1d232106ce3be0adf8fb7a9e57ee2bb82536408ec60978561ac0a5f',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.it.test',
      'hash' => 'dc80b84ed335e2989a36b3ffc7d670ff4106daa665e26df550c0dcf96915c056',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.jo.test',
      'hash' => '0c30dc4baccba8ddb4f2c55cfc2b79ceb736769bdc664e6189d1b5e7cd348e00',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.nl.test',
      'hash' => '33431240ce625582f22a5ae98066efd7ac57176a1d3e18a2a0f702ea43418637',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.nz.test',
      'hash' => '3a4cbb625f3c8de015b99ed1bc712f4cd41a3819ac9aea75170ad202297e46bc',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.pa.test',
      'hash' => 'd8fb12e636cd5028ab15e35f61d01d2d15e9e22f40724bf57f5958d6261720b7',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.ph.test',
      'hash' => '56727122712ea07bdded9df15449ffe1e605c5e64cebf599876a49e3b0bbb616',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.pk.test',
      'hash' => 'd061a6aa870b6a2607cfdb074d5d9ed5719e02fa298f69d38349b742335d8bb8',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.pl.test',
      'hash' => 'b9a2079d3d93909513d1c7b10054fddcea114529ac3f0d0cbbc674c547476180',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.ru.test',
      'hash' => 'f2f8c62e441ca34552754337f63ac7db81dceff3ebb984bfad3ad0ad19ca2072',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.se.test',
      'hash' => '7cb5c273d1f5d19533130da5417a4208c31f7ef8fd4d336972af202e64f05fd9',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.sg.test',
      'hash' => 'f76557ba04ad21f81b010f1cd6e649b7fa9eaf1df6acbcd7ac1c7fa60945f29e',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.ua.test',
      'hash' => '7441058b561f294da5dca24a367c5cb37bd043c4cb4a55606240d1843a244e56',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.uk.test',
      'hash' => 'a42cde9cb4ffbdab1974f56bbdf1f6fe9987f1a5d5713d11c0d3cdc6e0cb34c3',
    ))->values(array(
      'filename' => 'sites/all/modules/phone/tests/phone.za.test',
      'hash' => 'c5491ab663972aa23ae2f917a0fc605a6136f02e1b207d3fc650ed1f251359ee',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/comment/views_handler_argument_comment_user_uid.test',
      'hash' => 'b8b417ef0e05806a88bd7d5e2f7dcb41339fbf5b66f39311defc9fb65476d561',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/comment/views_handler_filter_comment_user_uid.test',
      'hash' => '347c6ffd4383706dbde844235aaf31cff44a22e95d2e6d8ef4da34a41b70edd1',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/field/views_fieldapi.test',
      'hash' => '53e6d57c2d1d6cd0cd92e15ca4077ba532214daf41e9c7c0f940c7c8dbd86a66',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_area_text.test',
      'hash' => 'af74a74a3357567b844606add76d7ca1271317778dd7bd245a216cf963c738b4',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_argument_null.test',
      'hash' => '1d174e1f467b905d67217bd755100d78ffeca4aa4ada5c4be40270cd6d30b721',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_argument_string.test',
      'hash' => '3d0213af0041146abb61dcdc750869ed773d0ac80cfa74ffbadfdd03b1f11c52',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_field.test',
      'hash' => 'af552bf825ab77486b3d0d156779b7c4806ce5a983c6116ad68b633daf9bb927',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_field_boolean.test',
      'hash' => 'd334b12a850f36b41fe89ab30a9d758fd3ce434286bd136404344b7b288460ae',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_field_counter.test',
      'hash' => '75b31942adf06b107f5ffd3c97545fde8cd1040b1d00f682e3c7c1320026e26c',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_field_custom.test',
      'hash' => '1446bc3d5a6b1180a79edfa46a5268dbf7f089836aa3bc45df00ddaff9dd0ce1',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_field_date.test',
      'hash' => '6f45326d7f74127956d9d8e4d7ad96a4beb0f66175fa40daf1d618d1a5fa996d',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_field_file_size.test',
      'hash' => '49184db68af398a54e81c8a76261acd861da8fd7846b9d51dcf476d61396bfb9',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_field_math.test',
      'hash' => '6e39e4f782e6b36151ceafb41a5509f7c661be79b393b24f6f5496d724535887',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_field_url.test',
      'hash' => 'b41f762a71594b438a2e60a79c8260ba54e6305635725b0747e29f0d3ffe08c9',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_field_xss.test',
      'hash' => 'f129ee16c03f84673e33990cbb2da5aa88c362f46e9ba1620b2a842ffd1c9cd2',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_filter_combine.test',
      'hash' => '05842d83a11822afe7d566835f5db9f0f94fdb27ddfc388d38138767bdf36f8b',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_filter_date.test',
      'hash' => '045cc449b68bbd5526071bf38c505b6d44f6c91868273c3120705c3bad250aee',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_filter_equality.test',
      'hash' => 'c88f21c9cbf1aae83393b26616908f8020c18fe378d76256c7ba192df2ec17af',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_filter_in_operator.test',
      'hash' => '89420a4071677232e0eb82b184b37b818a82bdb2ff90a8b21293f9ecb21808bf',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_filter_numeric.test',
      'hash' => '35ac7a34e696b979e86ef7209b6697098d9abe218e30a02cc4fe39fb11f2a852',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_filter_string.test',
      'hash' => 'b7d090780748faad478e619fd55673d746d4a0cf343d9e40ea96881324c34cbd',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_sort.test',
      'hash' => 'f4ff79e6bc54e83c4eb2777811f33702b7e9fe7416ef70ae00d100fa54d44fec',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_sort_date.test',
      'hash' => 'f548584d7c6a71cabd3ce07e04053a38df3f3e1685210ce8114238fd05344c10',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/handlers/views_handler_sort_random.test',
      'hash' => '4fdba9bf05a26720ffa97e7a37da65ddc9044bd2832f8c89007b82feb062f182',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/node/views_node_revision_relations.test',
      'hash' => '9467497a6d693615b48c8f57611a850002317bcb091b926d2efbbe56a4e61480',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/plugins/views_plugin_display.test',
      'hash' => '4a6b136543a60999604c54125fa9d4f5aa61a5dcc71e2133d89325d81bc0fc2d',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/styles/views_plugin_style.test',
      'hash' => 'fb6c3279645fbcc1126acb3e1c908189e5240c647f81dcfd9b0761570c99d269',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/styles/views_plugin_style_base.test',
      'hash' => '54fb7816d18416d8b0db67e9f55aa2aa50ac204eb9311be14b6700b7d7a95ae7',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/styles/views_plugin_style_jump_menu.test',
      'hash' => 'b88baa8aebe183943a6e4cf2df314fef13ac41b5844cd5fa4aa91557dd624895',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/styles/views_plugin_style_mapping.test',
      'hash' => 'a4e68bc8cfbeff4a1d9b8085fd115bfe7a8c4b84c049573fa0409b0dc8c2f053',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/styles/views_plugin_style_unformatted.test',
      'hash' => '033ca29d41af47cd7bd12d50fea6c956dde247202ebda9df7f637111481bb51d',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/taxonomy/views_handler_relationship_node_term_data.test',
      'hash' => '6074f5c7ae63225ea0cd26626ace6c017740e226f4d3c234e39869c31308223d',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/user/views_handler_field_user_name.test',
      'hash' => '69641b6da26d8daee9a2ceb2d0df56668bf09b86db1d4071c275b6e8d0885f9e',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/user/views_user.test',
      'hash' => 'fbb63b42a0b7051bd4d33cf36841f39d7cc13a63b0554eca431b2a08c19facae',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/user/views_user_argument_default.test',
      'hash' => '6423f2db7673763991b1fd0c452a7d84413c7dd888ca6c95545fadc531cfaaf4',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/user/views_user_argument_validate.test',
      'hash' => 'c88c9e5d162958f8924849758486a0d83822ada06088f5cf71bfbe76932d8d84',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/views_access.test',
      'hash' => 'f8b9d04b43c09a67ec722290a30408c1df8c163cf6e5863b41468bb4e381ee6f',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/views_analyze.test',
      'hash' => '5548e36c99bb626209d63e5cddbc31f49ad83865c983d2662c6826b328d24ffb',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/views_argument_default.test',
      'hash' => '5950937aae4608bba5b86f366ef3a56cc6518bbccfeaeacda79fa13246d220e4',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/views_argument_validator.test',
      'hash' => '31f8f49946c8aa3b03d6d9a2281bdfb11c54071b28e83fb3e827ca6ff5e38c88',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/views_basic.test',
      'hash' => '655bd33983f84bbea68a3f24bfab545d2c02f36a478566edf35a98a58ff0c6cf',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/views_cache.test',
      'hash' => '76316e1f026c2ab81ef91450b9d6d5985cbfab087f839ea0edd112209bf84fd9',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/views_exposed_form.test',
      'hash' => '2b2b16373af8ecade91d7c77bd8c2da8286a33bde554874f5d81399d201c3228',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/views_glossary.test',
      'hash' => '118d50177a68a6f88e3727e10f8bcc6f95176282cc42fbd604458eeb932a36e8',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/views_groupby.test',
      'hash' => 'ac6ca55f084f4884c06437815ccfa5c4d10bfef808c3f6f17a4f69537794a992',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/views_handlers.test',
      'hash' => 'a696e3d6b1748da03a04ac532f403700d07c920b9c405c628a6c94ea6764f501',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/views_module.test',
      'hash' => '3939798f2f679308903d4845f5625dd60df6110aec2615e33ab81e854d0b7e73',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/views_pager.test',
      'hash' => '6f448c8c13c5177afb35103119d6281958a2d6dbdfb96ae5f4ee77cb3b44adc5',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/views_query.test',
      'hash' => '1ab587994dc43b1315e9a534d005798aecaa14182ba23a2b445e56516b9528cb',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/views_translatable.test',
      'hash' => '6899c7b09ab72c262480cf78d200ecddfb683e8f2495438a55b35ae0e103a1b3',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/views_ui.test',
      'hash' => 'f9687a363d7cc2828739583e3eedeb68c99acd505ff4e3036c806a42b93a2688',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/views_upgrade.test',
      'hash' => 'c48bd74b85809dd78d963e525e38f3b6dd7e12aa249f73bd6a20247a40d6713a',
    ))->values(array(
      'filename' => 'sites/all/modules/views/tests/views_view.test',
      'hash' => 'a52e010d27cc2eb29804a3acd30f574adf11fad1f5860e431178b61cddbdbb69',
    ))->execute();
  }

}
#f31c4b35f9f220844db24b8a67b06dd5
