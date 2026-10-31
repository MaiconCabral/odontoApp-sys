import type { Agenda } from '~/types/agenda'

export const agendaMock: Agenda = {
  today: '2024-10-24',
  hours: ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'],
  procedures: ['Avaliação', 'Avaliação ortodôntica', 'Limpeza e prevenção', 'Restauração estética', 'Consulta de retorno'],
  freeSlotsToday: 3,
  professionals: [
    { id: 'carlos', name: 'Dr. Carlos Mendes', tone: 'cyan', selected: true },
    { id: 'ana', name: 'Dra. Ana Beatriz', tone: 'amber', selected: true },
    { id: 'felipe', name: 'Dr. Felipe Rocha', tone: 'green', selected: false },
  ],
  rooms: [
    { id: 'room-1', name: 'Sala 01 · Consultório', selected: true },
    { id: 'room-2', name: 'Sala 02 · Cirurgia', selected: true },
  ],
  events: [
    {
      id: 'event-1',
      patientName: 'Rafael Oliveira',
      date: '2024-10-22',
      hour: '09:00',
      procedure: 'Consulta',
      tone: 'amber',
      professionalId: 'ana',
      roomId: 'room-1',
    },
    {
      id: 'event-2',
      patientName: 'Camila Ferreira',
      date: '2024-10-24',
      hour: '10:00',
      procedure: 'Consulta',
      tone: 'blue',
      professionalId: 'carlos',
      roomId: 'room-1',
    },
    {
      id: 'event-3',
      patientName: 'Juliana Almeida',
      date: '2024-10-25',
      hour: '14:00',
      procedure: 'Consulta',
      tone: 'violet',
      professionalId: 'carlos',
      roomId: 'room-2',
    },
  ],
}
