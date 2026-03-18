<?php

namespace Modules\Pharmacie\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class StockPerime extends Notification
{
    use Queueable;

    protected $stocks;

    public function __construct($stocks)
    {
        $this->stocks = $stocks;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $count = $this->stocks->count();
        
        $message = (new MailMessage)
            ->subject('🚨 URGENT - ' . $count . ' produit(s) périmé(s) !')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('**ALERTE URGENTE** : ' . $count . ' lot(s) de produits sont **périmés**.')
            ->line('**Action immédiate requise** : Ces stocks doivent être retirés et détruits.');

        $message->line('---');
        $message->line('**Produits périmés :**');
        
        foreach ($this->stocks->take(10) as $stock) {
            $joursEcoules = now()->diffInDays($stock->date_peremption, false);
            
            $message->line(sprintf(
                '• **%s** - Lot: %s - Qté: %d - Périmé depuis: %d jours',
                $stock->produit->nom,
                $stock->numero_lot,
                $stock->quantite,
                abs($joursEcoules)
            ));
        }

        if ($this->stocks->count() > 10) {
            $message->line('... et ' . ($this->stocks->count() - 10) . ' autre(s)');
        }

        $message->error()
                ->action('Voir les stocks périmés', url('/api/pharmacie/stocks/perimes'))
                ->line('⚠️ Ces produits ne doivent plus être vendus.')
                ->salutation('Cordialement, Le système de gestion Pharmacie');

        return $message;
    }
}