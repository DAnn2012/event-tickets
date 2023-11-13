import { Card } from '@moderntribe/tickets/elements';
import React from 'react';
import TicketContainerHeaderTitle from '../../../../Ticket/app/editor/container-header/title/template';
import TicketContainerHeaderDescription from '../../../../Ticket/app/editor/container-header/description/template';
import TicketContainerHeaderPrice from '../../../../Ticket/app/editor/container-header/price/template';
import TicketContainerHeaderQuantity from '../../../../Ticket/app/editor/container-header/quantity/template';

const Uneditable = ({ tickets, cardsByTicketType, cardClassName }) => {
	const ticketTypes = tickets.reduce((acc, ticket) => {
		return acc.indexOf(ticket.type) === -1 ? [...acc, ticket.type] : acc;
	}, []);
	const ticketsByType = tickets.reduce((acc, ticket) => {
		const { type } = ticket;
		if (!acc[type]) {
			acc[type] = [];
		}
		acc[type].push(ticket);
		return acc;
	}, {});

	return ticketTypes.map((ticketType) => {
		return (
			<Card
				className={cardClassName}
				header={cardsByTicketType[ticketType].title}
			>
				{ticketsByType[ticketType].map((ticket, index) => (
					<article className="tribe-editor__ticket">
						<div className="tribe-editor__container-panel tribe-editor__container-panel--ticket tribe-editor__ticket__container">
							<div className="tribe-editor__container-panel__header">
								<>
									<div className="tribe-editor__ticket__container-header-details">
										<TicketContainerHeaderTitle
											title={ ticket.title}
											showAttendeeRegistrationIcons={ false }
										/>
										<TicketContainerHeaderDescription description={ ticket.description } />
									</div>
									<TicketContainerHeaderPrice
										available={ ticket.available }
										currencyDecimalPoint={ ticket.currencyDecimalPoint }
										currencyNumberOfDecimals={ ticket.currencyNumberOfDecimals }
										currencyPosition={ ticket.currencyPosition }
										currencySymbol={ ticket.currencySymbol }
										currencyThousandsSep={ ticket.currencyThousandsSep }
										isUnlimited={ ticket.capacityType === 'unlimited' }
										price={ ticket.price }
									/>
									<TicketContainerHeaderQuantity
										isShared={ ticket.isShared }
										isUnlimited={ ticket.capacityType === 'unlimited' }
										sold={ ticket.sold }
										capacity={ ticket.capacity }
										sharedSold={ ticketType.sharedSold }
										sharedCapacity={ ticketType.sharedCapacity }
									/>
								</>
							</div>
						</div>
					</article>
				))}
			</Card>
		);
	});
};

export default Uneditable;