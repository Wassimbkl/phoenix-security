<?php

namespace App\Controller;

use App\Entity\Shift;
use App\Form\ShiftType;
use App\Repository\ShiftRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/shift')]
class ShiftController extends AbstractController
{
    #[Route('/', name: 'app_shift_index', methods: ['GET'])]
    public function index(ShiftRepository $shiftRepository): Response
    {
        return $this->render('shift/index.html.twig', [
            'shifts' => $shiftRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_shift_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ShiftRepository $shiftRepository): Response
    {
        $shift = new Shift();
        $form = $this->createForm(ShiftType::class, $shift);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $shiftRepository->add($shift, true);

            return $this->redirectToRoute('app_shift_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('shift/new.html.twig', [
            'shift' => $shift,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_shift_show', methods: ['GET'])]
    public function show(Shift $shift): Response
    {
        return $this->render('shift/show.html.twig', [
            'shift' => $shift,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_shift_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Shift $shift, ShiftRepository $shiftRepository): Response
    {
        $form = $this->createForm(ShiftType::class, $shift);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $shiftRepository->add($shift, true);

            return $this->redirectToRoute('app_shift_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('shift/edit.html.twig', [
            'shift' => $shift,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_shift_delete', methods: ['POST'])]
    public function delete(Request $request, Shift $shift, ShiftRepository $shiftRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$shift->getId(), $request->request->get('_token'))) {
            $shiftRepository->remove($shift, true);
        }

        return $this->redirectToRoute('app_shift_index', [], Response::HTTP_SEE_OTHER);
    }
}
