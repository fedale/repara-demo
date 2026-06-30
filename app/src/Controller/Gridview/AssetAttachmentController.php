<?php

namespace App\Controller\Gridview;

use App\Entity\Asset\Asset;
use App\Entity\Asset\AssetAttachment;
use Fedale\GridviewBundle\Controller\AbstractCrudGridController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/gridview/asset-attachment', name: 'gridview_asset_attachment_')]
class AssetAttachmentController extends AbstractCrudGridController
{
    protected function getDataClass(): string
    {
        return AssetAttachment::class;
    }

    protected function viewConfig(): array
    {
        return [
            'id'       => 'asset_attachment',
            'title'    => 'Allegato asset',
            'addLabel' => 'Nuovo allegato',
            'exportFilename' => 'allegati-asset',
        ];
    }

    protected function dataConfig(): array
    {
        return [
            'models' => AssetAttachment::class,
            'pagination' => ['defaultPageSize' => 20],
            'sort' => [
                'id'   => ['asc' => ['a.id'],   'desc' => ['a.id'],   'default' => 'desc'],
                'name' => ['asc' => ['a.name'], 'desc' => ['a.name'], 'default' => 'asc'],
            ],
        ];
    }

    /** @return array<int, mixed> */
    protected function buildColumns(): array
    {
        $assetChoices = [];
        foreach ($this->em()->getRepository(Asset::class)->findAll() as $asset) {
            $assetChoices[$asset->getName()] = $asset->getId();
        }

        return [
            'id',
            [
                'attribute' => 'name',
                'label' => 'Nome',
                'filter' => ['type' => 'text'],
                'editable' => true,
                'control' => [
                    'type' => 'text',
                    'required' => true,
                    'requiredMessage' => 'Il nome è obbligatorio.',
                ],
            ],
            [
                'attribute' => 'asset',
                'label' => 'Asset',
                'type' => 'relation',
                'value' => fn(array $data) => $data['asset']['name'] ?? '—',
                'filter' => [
                    'type' => 'relation',
                    'options' => ['choices' => $assetChoices, 'multiple' => true, 'searchable' => true],
                ],
                'control' => ['options' => ['class' => Asset::class, 'choice_label' => 'name']],
            ],
            // Media: in griglia mostra l'anteprima (immagine inline) o un link di
            // download; nel form di create offre il pulsante di upload. La gridview
            // gestisce la fase 1 (ricezione del file); la fase 2 (storage) è delegata
            // a VichUploader settando imageFile sull'entità (vedi closure sotto).
            [
                'attribute' => 'filename',
                'label' => 'File',
                'type' => 'media',
                // `valueGetter` (non `value`): fornisce la URL come *raw value* così
                // il tipo `media` la renderizza come <img>. La legacy `value` farebbe
                // override dell'intera cella, bypassando MediaType e stampando il path.
                'valueGetter' => fn (array $d) => !empty($d['filename'])
                    ? '/' . trim((string) ($d['path'] ?? ''), '/') . '/' . $d['filename']
                    : null,
                'control' => [
                    'type' => 'media',
                    'required' => true,
                    'modes' => ['create'],
                    // VichUploader: al flush sposta il file sotto public/uploads/asset
                    // e popola filename/size/type (config/packages/vich_uploader.yaml).
                    'upload' => function (UploadedFile $file, AssetAttachment $a): void {
                        $a->setImageFile($file);
                        $a->setPath('uploads/asset');
                        if (!$a->getName()) {
                            $a->setName($file->getClientOriginalName());
                        }
                    },
                ],
            ],
            ['attribute' => 'type', 'label' => 'Tipo', 'filter' => ['type' => 'text']],
            ['attribute' => 'path', 'label' => 'Percorso', 'visible' => false],
            ['attribute' => 'size', 'label' => 'Dimensione', 'type' => 'number'],
            [
                'attribute' => 'active',
                'label' => 'Attivo',
                'type' => 'boolean',
                'filter' => ['type' => 'boolean'],
                'batchUpdate' => true,
                'editable' => true,
                'control' => ['type' => 'boolean', 'required' => false],
            ],
            ['type' => 'action', 'label' => 'Azioni'],
        ];
    }
}
